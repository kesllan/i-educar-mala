<?php

use App\Models\Country;
use App\Models\State;

return new class extends clsCadastro {
    public $ref_cod_aluno;

    public $sequencial;

    public $ref_usuario_exc;

    public $ref_usuario_cad;

    public $ano;

    public $carga_horaria;

    public $dias_letivos;

    public $ref_cod_escola;

    public $escola;

    public $escola_cidade;

    public $escola_uf;  

    public $observacao;

    public $aprovado;

    public $data_cadastro;

    public $data_exclusao;

    public $ativo;

    public $posicao;

    public $ref_cod_instituicao;

    public $nm_curso;

    public $nm_serie;

    public $origem;

    public $extra_curricular;

    public $ref_cod_matricula;

    public $faltas_globalizadas;

    public $cb_faltas_globalizadas;

    public $frequencia;

    //------INCLUI DISCIPLINA------//

    public $historico_disciplinas;

    public $nm_disciplina;

    public $nota;

    public $faltas;

    public $ordenamento;

    public $carga_horaria_disciplina;

    public $disciplinaDependencia;

    public $excluir_disciplina;

    public $ultimo_sequencial;

    public $aceleracao;

    public $dependencia;

	public $promocao;

    public function Inicializar()
    {
        $retorno = 'Novo';

        $this->sequencial = $_GET['sequencial'];
        $this->ref_cod_aluno = $_GET['ref_cod_aluno'];

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(578, $this->pessoa_logada, 7, "educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");

        if (is_numeric($this->ref_cod_aluno) && is_numeric($this->sequencial)) {
            $obj = new clsPmieducarHistoricoEscolar($this->ref_cod_aluno, $this->sequencial);
            $registro = $obj->detalhe();

            if ($registro) {
                foreach ($registro as $campo => $val) {  // passa todos os valores obtidos no registro para atributos do objeto
                    $this->$campo = $val;
                }

                if (!$this->origem) {
                    $this->simpleRedirect("educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");
                }

                if ($obj_permissoes->permissao_excluir(578, $this->pessoa_logada, 7)) {
                    $this->fexcluir = true;
                }

                if (!isset($_GET['copia'])) {
                    $retorno = 'Editar';
                } else {
                    $this->fexcluir = false;
                }
            }
        }

        $this->url_cancelar = ($retorno == 'Editar') ? "educar_historico_escolar_det.php?ref_cod_aluno={$registro['ref_cod_aluno']}&sequencial={$registro['sequencial']}" : "educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}";
        $this->nome_url_cancelar = 'Cancelar';
        $this->dependencia = dbBool($this->dependencia);

        $this->breadcrumb('Atualização de históricos escolares', [
            url('intranet/educar_index.php') => 'Escola',
        ]);

        return $retorno;
    }

    public function Gerar()
    {
        if (isset($_GET['ref_cod_aluno'], $_GET['sequencial'])) {
            $objCodNomeEscola = new clsPmieducarHistoricoEscolar($_GET['ref_cod_aluno'], $_GET['sequencial']);
            $registro = $objCodNomeEscola->detalhe();

            if ($registro) {
                $nomeEscola = $registro['escola'];
                $codigoEscola = $registro['ref_cod_escola'];
            }
        }

        if ($_POST) {
            foreach ($_POST as $campo => $val) {
                $this->$campo = (!$this->$campo) ? $val : $this->$campo;
            }
        }

        // primary keys
        $this->campoOculto('ref_cod_aluno', $this->ref_cod_aluno);
        $this->campoOculto('sequencial', $this->sequencial);
        $this->campoOculto('codigoEscola', $codigoEscola);
        $this->campoOculto('nomeEscola', $nomeEscola);
        $this->campoOculto('numeroSequencial', $_GET['sequencial']);

        $obj_aluno = new clsPmieducarAluno();
        $lst_aluno = $obj_aluno->lista($this->ref_cod_aluno, null, null, null, null, null, null, null, null, null, 1);

        if (is_array($lst_aluno)) {
            $det_aluno = array_shift($lst_aluno);
            $this->nm_aluno = $det_aluno['nome_aluno'];
            $this->campoRotulo('nm_aluno', 'Aluno', $this->nm_aluno);
        }

        $obj_nivelUser = new clsPermissoes();
        $user_nivel = $obj_nivelUser->nivel_acesso($this->pessoa_logada);

        if ($user_nivel != App_Model_NivelTipoUsuario::POLI_INSTITUCIONAL) {
            $obj_permissoes = new clsPermissoes();
            $this->ref_cod_instituicao = $obj_permissoes->getInstituicao($this->pessoa_logada);
            $habilitaCargaHoraria = $this->habilitaCargaHoraria($this->ref_cod_instituicao);
        }
        $this->inputsHelper()->dynamic(['instituicao', 'escolaSemFiltroPorUsuario'], ['required' => false]);
        $labelHintEscolaForaDoMunicipio = 'Transferência para uma escola externa (outro município, particular, etc)';
        $this->inputsHelper()->checkbox('escola_em_outro_municipio', ['label' => 'Escola em outro município ou fora da rede?', '<br>label_hint' => $labelHintEscolaForaDoMunicipio]);

        $escola_options = [
            'required' => false,
            'label' => 'Nome da escola',
            'value' => $this->escola,
            'max_length' => 255,
            'size' => 80,
        ];
        $this->inputsHelper()->text('escola', $escola_options);

        $countryId = null;

        if ($this->escola_uf) {
            $state = State::findByAbbreviation($this->escola_uf);

            $countryId = $state->country_id;
        }

        $lista_pais_origem = ['' => 'Selecione um país'] + Country::query()->orderBy('name')->pluck('name', 'id')->toArray();

        $this->campoLista('idpais', 'País da Escola', $lista_pais_origem, $countryId ?? 45);

        $lista_estado = ['' => 'Selecione um estado'] + State::getListKeyAbbreviation()->toArray();

        $this->campoLista('escola_uf', 'Estado da Escola', $lista_estado, $this->escola_uf);

        $options = ['label' => 'Cidade da Escola', 'required' => true];

        $helperOptions = [
            'objectName' => 'escola_cidade',
            'hiddenInputOptions' => ['options' => ['value' => mb_strtoupper($this->escola_cidade)]],
            'apiResource' => 'municipio-name-search',
            'placeholder' => 'Informe o nome da cidade',
            'checkIfExists' => false,
        ];

        $this->inputsHelper()->simpleSearchMunicipio('', $options, $helperOptions);

        $this->campoTexto('nm_curso', 'Curso', $this->nm_curso, 30, 255, false, false, false, _cl('historico.cadastro.curso_detalhe'));

        $opcoesGradeCurso = $this->getOpcoesGradeCurso();
        $this->campoLista('historico_grade_curso_id', 'Grade curso', $opcoesGradeCurso, $this->historico_grade_curso_id);

        $this->campoTexto('nm_serie', _cl('historico.cadastro.serie'), $this->nm_serie, 30, 255, true);
        $this->campoCheck('dependencia', 'Histórico de dependência', $this->dependencia);
        $this->campoNumero('ano', 'Ano', $this->ano, 4, 4, true);

        if ($this->validaControlePosicaoHistorico()) {
            $this->campoNumero('posicao', 'Posição', $this->posicao, 1, 1, true, 'Informe a coluna equivalente a série/ano/etapa a qual o histórico pertence. Ex.: 1º ano informe 1, 2º ano informe 2');
        }

        $this->campoNumero('carga_horaria', 'Carga Horária', $this->carga_horaria, 8, 8, false);
        $this->campoCheck('cb_faltas_globalizadas', 'Faltas Globalizadas', is_numeric($this->faltas_globalizadas) ? 'on' : '');
        $this->campoNumero('faltas_globalizadas', 'Faltas Globalizadas', $this->faltas_globalizadas, 4, 4, false);
        $this->campoNumero('promocao', 'Mínimo Para Promoção', $this->promocao, 3, 3, false);
        $this->campoNumero('dias_letivos', 'Dias Letivos', $this->dias_letivos, 3, 3, false);        
        $this->campoMonetario('frequencia', 'Frequência', $this->frequencia, 8, 6, false);
        $this->campoCheck('extra_curricular', 'Extra-Curricular', $this->extra_curricular);
        $this->campoCheck('aceleracao', 'Aceleração', $this->aceleracao);
       

        $obs_options = [
            'required' => false,
            'label' => 'Observação',
            'value' => $this->observacao,
        	'placeholder' => 'Use a TAG <b> para destacar em negrito. Ex.: <b>texto</b>',
        ];
        $this->inputsHelper()->textArea('observacao', $obs_options);

        $opcoes = [
            '' => 'Selecione',
            1 => 'Aprovado',
            2 => 'Reprovado',
            3 => 'Cursando',
            4 => 'Transferido',
            5 => 'Reclassificado',
            6 => 'Abandono',
            12 => 'Aprovado com dependência',
            13 => 'Aprovado pelo conselho',
            14 => 'Reprovado por faltas'
        ];

        $this->campoLista('aprovado', 'Situação', $opcoes, $this->aprovado);
        $this->campoTexto('registro', 'Registro (arquivo)', $this->registro, 30, 50, false);
        $this->campoTexto('livro', 'Livro', $this->livro, 30, 50, false);
        $this->campoTexto('folha', 'Folha', $this->folha, 30, 50, false);

        //---------------------INCLUI DISCIPLINAS---------------------//
        $this->campoQuebra();

        if (is_numeric($this->ref_cod_aluno) && is_numeric($this->sequencial) && !$_POST) {
            $obj = new clsPmieducarHistoricoDisciplinas();
            $obj->setOrderby('nm_disciplina ASC');
            $registros = $obj->lista(null, $this->ref_cod_aluno, $this->sequencial);
            $qtd_disciplinas = 0;

            if ($registros) {
                foreach ($registros as $campo) {
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['nm_disciplina'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['tipo_base'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['nota'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['faltas'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['carga_horaria_disciplina'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['ordenamento'];
                    $this->historico_disciplinas[$qtd_disciplinas][] = dbBool($campo['dependencia']) ? 1 : 0;
                    $this->historico_disciplinas[$qtd_disciplinas][] = $campo['sequencial'];
                    $qtd_disciplinas++;
                }
            }
        }

        // Tipo Base
        $tipoBase = ComponenteCurricular_Model_TipoBase::getInstance()->getEnums();

        $this->campoTabelaInicio('notas', 'Notas', ['Disciplina', 'Base curricular', 'Nota', 'Faltas', 'C.H', 'Ordem', 'Dependência'], $this->historico_disciplinas);
        $this->campoTexto('nm_disciplina', 'Disciplina', $this->nm_disciplina, 30, 255, false, false, false, '', '', '', 'onfocus');
        $this->campoLista('tipo_base', 'Base curricular', $tipoBase, $this->tipo_base, '', false, '', '', false, false);
        $this->campoTexto('nota', 'Nota', $this->nota, 10, 255, false);
        $this->campoNumero('faltas', 'Faltas', $this->faltas, 3, 3, false);
        $this->campoNumero('carga_horaria_disciplina', 'carga_horaria_disciplina', $this->carga_horaria_disciplina, 6, 6, false, null, null, null, null, null, $habilitaCargaHoraria);
        $this->campoNumero('ordenamento', 'ordenamento', $this->ordenamento, 3, 3, false);
        $options = ['label' => 'Dependência', 'value' => $this->disciplinaDependencia];
        $this->inputsHelper()->checkbox('disciplinaDependencia', $options);

        $this->campoTabelaFim();

        $this->campoQuebra();

        //---------------------FIM INCLUI DISCIPLINAS---------------------//

        // carrega estilo para feedback messages, para exibir msg validação frequencia.

        $style = '/modules/Portabilis/Assets/Stylesheets/Frontend.css';
        Portabilis_View_Helper_Application::loadStylesheet($this, $style);

        Portabilis_View_Helper_Application::loadJavascript(
            $this,
            [
                '/modules/Portabilis/Assets/Javascripts/Utils.js',
                '/modules/Portabilis/Assets/Javascripts/Frontend/Inputs/SimpleSearch.js',
                '/modules/Portabilis/Assets/Javascripts/Validator.js',
                '/modules/Cadastro/Assets/Javascripts/HistoricoEscolar.js'
            ]
        );
    }

    public function Novo()
    {
        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(578, $this->pessoa_logada, 7, "educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");

        $this->carga_horaria = is_numeric($this->carga_horaria) ? doubleval($this->carga_horaria) : $this->carga_horaria;
        $this->frequencia = $this->fixupFrequencia($this->frequencia);
        $this->extra_curricular = is_null($this->extra_curricular) ? 0 : 1;

        $obj = new clsPmieducarHistoricoEscolar(
            $this->ref_cod_aluno,
            null,
            null,
            $this->pessoa_logada,
            $this->nm_serie,
            $this->ano,
            $this->carga_horaria,
            $this->dias_letivos,
            mb_strtoupper($this->escola),
            mb_strtoupper($this->escola_cidade),
            $this->escola_uf,            
            $this->observacao,
            $this->aprovado,
            null,
            null,
            1,
            $this->faltas_globalizadas,            
            $this->ref_cod_instituicao,
            1,
            $this->extra_curricular,
            null,
            $this->frequencia,
            $this->registro,
            $this->livro,
            $this->folha,
            $this->nm_curso,
            $this->historico_grade_curso_id,
            $this->aceleracao,
            $this->ref_cod_escola,
            !is_null($this->dependencia),
            $this->posicao,
        	$this->promocao
        );
        $cadastrou = $obj->cadastra();

        if ($cadastrou) {

            //--------------CADASTRA DISCIPLINAS--------------//
            if ($this->nm_disciplina) {
                $sequencial = 1;

                foreach ($this->nm_disciplina as $key => $disciplina) {
                    $obj_historico = new clsPmieducarHistoricoEscolar();
                    $this->sequencial = $obj_historico->getMaxSequencial($this->ref_cod_aluno);

                    $obj = new clsPmieducarHistoricoDisciplinas($sequencial, $this->ref_cod_aluno, $this->sequencial, $disciplina, $this->nota[$key], $this->faltas[$key], $this->ordenamento[$key], $this->carga_horaria_disciplina[$key], $this->disciplinaDependencia[$key] == 'on' ? true : false, $this->tipo_base[$key]);
                    $cadastrou1 = $obj->cadastra();

                    if (!$cadastrou1) {
                        $this->mensagem = 'Cadastro não realizado.<br>';

                        return false;
                    }

                    $sequencial++;
                }

                $this->mensagem .= 'Cadastro efetuado com sucesso.<br>';
                $this->simpleRedirect("educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");
            }
            //--------------FIM CADASTRA DISCIPLINAS--------------//
        }
        $this->mensagem = 'Cadastro não realizado.<br>';

        return false;
    }

    public function Editar()
    {
        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(578, $this->pessoa_logada, 7, "educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");

        $this->carga_horaria = is_numeric($this->carga_horaria) ? (double) $this->carga_horaria : $this->carga_horaria;
        $this->frequencia = $this->fixupFrequencia($this->frequencia);

        $faltasGlobalizadas = $this->faltas_globalizadas;

        if ($this->cb_faltas_globalizadas !== 'on') {
            $faltasGlobalizadas = 'NULL';
        }

        $this->aceleracao = is_null($this->aceleracao) ? 0 : 1;
        $this->extra_curricular = is_null($this->extra_curricular) ? 0 : 1;

        $obj = new clsPmieducarHistoricoEscolar(
            $this->ref_cod_aluno,
            $this->sequencial,
            $this->pessoa_logada,
            null,
            $this->nm_serie,
            $this->ano,
            $this->carga_horaria,
            $this->dias_letivos,
            mb_strtoupper($this->escola),
            mb_strtoupper($this->escola_cidade),
            $this->escola_uf,            
            $this->observacao,
            $this->aprovado,
            null,
            null,
            1,
            $faltasGlobalizadas,            
            $this->ref_cod_instituicao,
            1,
            $this->extra_curricular,
            null,
            $this->frequencia,
            $this->registro,
            $this->livro,
            $this->folha,
            $this->nm_curso,
            $this->historico_grade_curso_id,
            $this->aceleracao,
            $this->ref_cod_escola,
            !is_null($this->dependencia),
            $this->posicao,
        	$this->promocao
        );

        $editou = $obj->edita();

        if ($editou) {

            //--------------EDITA DISCIPLINAS--------------//
            if ($this->nm_disciplina) {
                $obj = new clsPmieducarHistoricoDisciplinas();
                $excluiu = $obj->excluirTodos($this->ref_cod_aluno, $this->sequencial);
                if ($excluiu) {
                    $sequencial = 1;
                    foreach ($this->nm_disciplina as $key => $disciplina) {
                        //$campo['nm_disciplina_'] = urldecode($campo['nm_disciplina_']);

                        $obj = new clsPmieducarHistoricoDisciplinas($sequencial, $this->ref_cod_aluno, $this->sequencial, $disciplina, $this->nota[$key], $this->faltas[$key], $this->ordenamento[$key], $this->carga_horaria_disciplina[$key], $this->disciplinaDependencia[$key] == 'on' ? true : false, $this->tipo_base[$key]);
                        $cadastrou1 = $obj->cadastra();
                        if (!$cadastrou1) {
                            $this->mensagem = 'Cadastro não realizado.<br>';

                            return false;
                        }
                        $sequencial++;
                    }
                }
                $this->mensagem .= 'Edição efetuada com sucesso.<br>';
                $this->simpleRedirect("educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");
            }
            //--------------FIM EDITA DISCIPLINAS--------------//
        }
        $this->mensagem = 'Edição não realizada.<br>';

        return false;
    }

    public function Excluir()
    {
        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_excluir(578, $this->pessoa_logada, 7, "educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");

        $obj = new clsPmieducarHistoricoEscolar($this->ref_cod_aluno, $this->sequencial, $this->pessoa_logada, null, null, null, null, null, null, null, null, null, null, null, null, 0);
        $historicoEscolar = $obj->detalhe();
        $excluiu = $obj->excluir();
        if ($excluiu) {
            $obj = new clsPmieducarHistoricoDisciplinas();
            $excluiu = $obj->excluirTodos($this->ref_cod_aluno, $this->sequencial);
            if ($excluiu) {
                $this->mensagem .= 'Exclusão efetuada com sucesso.<br>';
                $this->simpleRedirect("educar_historico_escolar_lst.php?ref_cod_aluno={$this->ref_cod_aluno}");
            }
        }

        $this->mensagem = 'Exclusão não realizada.<br>';

        return false;
    }

    protected function fixupFrequencia($frequencia)
    {
        if (strpos($frequencia, ',')) {
            $frequencia = str_replace('.', '', $frequencia);
            $frequencia = str_replace(',', '.', $frequencia);
        }

        return $frequencia;
    }

    public function habilitaCargaHoraria($instituicao)
    {
        $obj_instituicao = new clsPmieducarInstituicao($instituicao);
        $detalhe_instituicao = $obj_instituicao->detalhe();
        $valorPermitirCargaHoraria = dbBool($detalhe_instituicao['permitir_carga_horaria']);

        return $valorPermitirCargaHoraria;
    }

    public function getOpcoesGradeCurso()
    {
        $db = new clsBanco();
        $sql = 'select * from pmieducar.historico_grade_curso where ativo = 1';
        $db->Consulta($sql);

        $opcoes = ['' => 'Selecione'];
        while ($db->ProximoRegistro()) {
            $record = $db->Tupla();
            $opcoes[$record['id']] = $record['descricao_etapa'];
        }

        return $opcoes;
    }

    public function validaControlePosicaoHistorico()
    {
        $obj = new clsPmieducarInstituicao;
        //Busca instituicao ativa
        $lst = $obj->lista(null, null, null, null, null, null, null, null, null, null, null, null, null, 1);

        return dbBool($lst[0]['controlar_posicao_historicos']);
    }

    public function Formular()
    {
        $this->title = 'Histórico Escolar';
        $this->processoAp = '578';
    }
};
