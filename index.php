<?php

session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \CONASS\Page;
use \CONASS\PageAdmin;
use \CONASS\Model\User;
use \CONASS\Model\Usuarios;
use \CONASS\Model\Contratos;
use \CONASS\Model\FornecedorConsultor;
use \CONASS\Model\Responsavel;
use \CONASS\Model\Aditivos;
use \CONASS\Model\Parcelas;
use \CONASS\Model\ParcelasPagas;
use \CONASS\Model\fontesRecursos;
use \CONASS\Model\StringError;
use \CONASS\Model\Atividades;
use \CONASS\Model\Categoria;
use \CONASS\Model\Avaliacao;
use \CONASS\Model\Acao;
use \CONASS\Model\Notificacao;

require_once("functions.php");


$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {

    User:: verifyLogin();

    $page = new Page([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("login", [
        'error' => User::getError()
    ]);
});

$app->get('/login', function() {

    $page = new Page([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("login", [
        'error' => User::getError()
    ]);
});


$app->post('/admin', function() {

    try {

        User::login($_POST["user_name"], $_POST["password"]);
    } catch (Exception $e) {

        User::setError($e->getMessage());
    }


    header("Location: /contratos");
    exit;
});



$app->get('/admin/logout', function() {

    User:: logout();

    header("Location: /login");
    exit;
});

$app->get("/admin/forgot", function() {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("forgot");
});

$app->post("/admin/forgot", function() {

    $user = User::getForgot($_POST["email"]);

    header("Location: /admin/forgot/sent");
    exit;
});

$app->get("/admin/forgot/sent", function() {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function() {

    $user = User::validForgotDecrypt($_GET["code"]);


    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);


    $page->setTpl("forgot-reset", array(
        "name" => $user["nome"],
        "code" => $_GET["code"]
    ));
});

$app->post("/admin/forgot/reset", function() {


    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);

    $user = new User();

    $user->get((int) $forgot["id_usuario"]);

    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
        "cost" => 12
    ]);

    $user->setPassword($password);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);
    $page->setTpl("forgot-reset-success");
});

$app->get("/usuario", function() {

    User:: verifyLogin();

    $usuarios = new Usuarios();

    $page = new PageAdmin();

    $page->setTpl("users", array(
        "usuario" => $usuarios->ListAll(),
        "error" => User::getError()
    ));
});

$app->get("/usuario/create", function() {

    User:: verifyLogin();

    $page = new PageAdmin([
        "footer" => false
    ]);

    $page->setTpl("users-create");
});

$app->post("/admin/users/create", function() {

    User:: verifyLogin();

    $usuario = new Usuarios();

    try {
        $usuario->setNome($_POST["desperson"]);
        $usuario->setUser_name($_POST["deslogin"]);
        $usuario->setEmail($_POST["desemail"]);
        $usuario->setPass($_POST["despassword"]);

        $result = $usuario->save();
    } catch (Exception $exc) {
        User::setError($e->getMessage());
        header('Location: /usuario/create');
        exit;
    }

    header('Location: /contratos');
    exit;
});

$app->get("/usuario/update/:id", function($id) {

    User:: verifyLogin();

    $usuario = new Usuarios();
    $usuario->get($id);
    $page = new PageAdmin();


    $page->setTpl("users-update", array(
        "id_usuario" => $usuario->getId_usuario(),
        "nome" => $usuario->getNome(),
        "user_name" => $usuario->getUser_name(),
        "email" => $usuario->getEmail(),
        "error" => User::getError()
    ));
});


$app->post("/usuario/update", function() {

    User:: verifyLogin();

    $usuario = new Usuarios();

    try {

        $usuario->setId_usuario($_POST["id_usuario"]);
        $usuario->setNome($_POST["nome"]);
        $usuario->setUser_name($_POST["user_name"]);
        $usuario->setEmail($_POST["email"]);

        $usuario->update();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header('Location: /usuario/update/' . $usuario->getId_usuario());
        exit;
    }

    header('Location: /usuario');
    exit;
});

$app->get("/pass/senha", function() {
    User:: verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("pass-reset", array(
    ));
});

$app->post("/pass/senha", function() {
    User::verifyLogin();
    $user = new User();

    $usuario = new Usuarios();

    try {

        $user->get($_POST["id_usuario"]);

        $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
            "cost" => 12
        ]);

        $user->setPassword($password);

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
            );
        }

        // Por último, destrói a sessão
        session_destroy();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header('Location: /contratos');

        exit;
    }

    header('Location: /contratos');
    exit;
});
/* =======================================================================
 *                                                                      *
 *                  BLOCO CONTRATO                                      *
 * ====================================================================== */
$app->get('/contratos', function() {

    User:: verifyLogin();


    $contratos = Contratos::ListAllContratos();

    $page = new PageAdmin();

    $page->setTpl("contratos", array(
        "contrato" => $contratos,
        "error" => User::getError()
    ));
});

$app->get("/contrato/create", function() {

    User:: verifyLogin();

    $fornecedor = FornecedorConsultor::ListAllFornecedorConsultor();
    $responsavel = Responsavel::ListAllResponsavel();

    $page = new PageAdmin();


    $page->setTpl("contrato-create", array(
        "fornecedor" => $fornecedor,
        "responsavel" => $responsavel,
        "error" => User::getError()
    ));
});

$app->post("/contrato/create", function() {

    User::verifyLogin();
    $contrato = new Contratos();

    try {

        $contrato->setNumero_contrato($_POST["numero_contrato"]);
        $contrato->setFk_id_fornecedor_consultor($_POST["fk_id_fornecedor_consultor"]);
        $contrato->setObjeto($_POST["objeto"]);
        $contrato->setData_inicio($_POST["data_inicio"]);
        $contrato->setData_fim($_POST["data_fim"]);
        $contrato->setValor_total($_POST["valor_total"]);
        $contrato->setFk_id_user($_POST["fk_id_user"]);
        $contrato->setFk_id_email($_POST["fk_id_email"]);
        $contrato->setContparcela((int) $_POST["confparcela"]);
        $contrato->setIdsituacao(1);

        $result = $contrato->save();

        if ($result):
            if ((int) $_POST["confparcela"] > 0):
                $parcela = new Parcelas();

                $parcela->setFk_contrato($result);
                $parcela->setContparcela((int) $_POST["confparcela"]);
                $parcela->setData($_POST["data-parcela"]);
                $parcela->setValor_parcela($_POST["valor-parcela"]);

                $parcela->save();

            endif;

        endif;
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header('Location: /contratos');

        exit;
    }

    header('Location: /contrato/' . $result);
    exit;
});

$app->get("/contrato/:idcontrato", function($idcontrato) {
    User:: verifyLogin();
    $contrato = new Contratos();
    $contrato->get((int) $idcontrato);

    $adtivo = Aditivos::listAllAditivo($contrato->getPk_di());
    $parcela = Parcelas::listParcelasContratos($contrato->getPk_di());
    $parcelasPagas = ParcelasPagas::listPagParcelas($contrato->getPk_di());
    $valorTotalAditivo = Aditivos::valorTotalAditivo($contrato->getPk_di());
    $valorContrato = $contrato->getValor_total();
    $fontesRecursos = fontesRecursos::listAll();
    //valor pago 
    $vl = ParcelasPagas::pagamentoEfetuado($contrato->getPk_di());
    $saldo = $valorContrato - $vl;

    $page = new PageAdmin();

    $page->setTpl("contratos-view", [
        'pk_di' => $contrato->getPk_di(),
        'numero_contrato' => $contrato->getNumero_contrato(),
        'contratado' => $contrato->getNome_razao(),
        'responsavel' => $contrato->getNome_responsavel(),
        'objeto' => $contrato->getObjeto(),
        'data_inicio' => $contrato->getData_inicio(),
        'data_fim' => $contrato->getData_fim(),
        'valor_total' => number_format($valorContrato, 2, ',', '.'),
        'aditivo' => $adtivo,
        'parcela' => $parcela,
        'parcelaspagas' => $parcelasPagas,
        'saldoContratual' => number_format($saldo, 2, ',', '.'),
        'saldo' => $saldo,
        'pago' => $vl,
        'fontesRecursos' => $fontesRecursos,
        "error" => User::getError()
    ]);
});

$app->get("/contrato/update/:idcontrato", function($idcontrato) {
    User:: verifyLogin();
    $contrato = new Contratos();
    $contrato->get((int) $idcontrato);

    $adtivo = Aditivos::listAllAditivo($contrato->getPk_di());
    $parcela = Parcelas::listParcelasContratos($contrato->getPk_di());
    $parcelasPagas = ParcelasPagas::listPagParcelas($contrato->getPk_di());
    $valorTotalAditivo = Aditivos::valorTotalAditivo($contrato->getPk_di());
    $valorContrato = $contrato->getValor_total();
    $fontesRecursos = fontesRecursos::listAll();
    //valor pago 
    $vl = ParcelasPagas::pagamentoEfetuado($contrato->getPk_di());
    $saldo = $valorContrato - $vl;

    $page = new PageAdmin();

    $page->setTpl("contratos-update", [
        'idcontrato' => $contrato->getPk_di(),
        'numero_contrato' => $contrato->getNumero_contrato(),
        'contratado' => $contrato->getNome_razao(),
        'idcontratado' => $contrato->getFk_id_fornecedor_consultor(),
        'responsavel' => $contrato->getNome_responsavel(),
        'idresponsavel' => $contrato->getFk_id_email(),
        'objeto' => $contrato->getObjeto(),
        'data_inicio' => $contrato->getData_inicio(),
        'data_fim' => $contrato->getData_fim(),
        'valor_total' => number_format($contrato->getValor_contratoPai(), 2, ',', '.'),
        'aditivo' => $adtivo,
        'parcela' => $parcela,
        'parcelaspagas' => $parcelasPagas,
        'saldoContratual' => number_format($saldo, 2, ',', '.'),
        'saldo' => $saldo,
        'pago' => $vl,
        'fontesRecursos' => $fontesRecursos,
        "error" => User::getError()
    ]);
});

$app->post("/contrato/update", function() {

    User::verifyLogin();
    $contrato = new Contratos();
    $parcela = new Parcelas();

   // (int) $contParcela = count($_POST["id_parcela"]) - 1;

    try {
  
        $contrato->setPk_di($_POST["numerocontrato"]);
        $contrato->setNumero_contrato($_POST["numero_contrato"]);
        $contrato->setObjeto($_POST["objeto"]);
        $contrato->setData_inicio($_POST["data_inicio"]);
        $contrato->setData_fim($_POST["data_fim"]);
        $contrato->setValor_total($_POST["valor_total"]);
        $contrato->setFk_id_user($_POST["fk_id_user"]);
        $contrato->setFk_id_email($_POST["fk_id_email"]);
        $contrato->setIdsituacao(1);

        $result = $contrato->update();

        
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header('Location: /contratos');

        exit;
    }

    header('Location: /contrato/' . $contrato->getPk_di());
    exit;
});

/* =======================================================================
 *                                                                      *
 *                  FINAL BLOCO CONTRATO                                *
 * ====================================================================== */

/* =======================================================================
 *                                                                        *
 *                  PAGAMENTO CONTRATO                                    *
 * ====================================================================== */


$app->get('/produto-create/:idcontrato', function($idcontrato) {
    User:: verifyLogin();
    $contrato = new Contratos();
    $categoria = Categoria::getAll();
    $avaliacao = Avaliacao::getAll();
    $acao = Acao::getAll();

    $contrato->get($idcontrato);

    $page = new PageAdmin();

    $page->setTpl("produto-create", array(
        "idcontrato" => $contrato->getPk_di(),
        "contratado" => $contrato->getNome_razao(),
        "numero_contrato" => $contrato->getNumero_contrato(),
        "fontesRecursos" => fontesRecursos::listAll(),
        "categoria" => $categoria,
        "avaliacao" => $avaliacao,
        "acao" => $acao,
        "error" => StringError::getError()
    ));
});

$app->get("/vencimento", function() {

    function geraTimestamp($data) {
        $partes = explode('/', $data);
        return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
    }

    $contrato = Contratos::vencimentoContrato();
    $dataatual = date('d/m/Y');

    foreach ($contrato as $rec):
        extract($rec);
        //formatando a data
        $dataVencimento = implode("/", array_reverse(explode("-", $data_fim)));

        //pegando o timestamp da data
        $timeInicial = geraTimestamp($dataatual);
        $timeFinal = geraTimestamp($dataVencimento);
        //calcular diferença de segundos entre as datas

        $diferenca = $timeFinal - $timeInicial;
        //calcula a diferença de dias

        $dias = (int) floor($diferenca / (60 * 60 * 24));

        echo "A diferença entre as datas " . $dataatual . " e " . $dataVencimento . " é de <strong>" . $dias . "</strong> dias - " . $numero_contrato;


        echo "<br>";

    endforeach;
});

$app->post('/produto/create', function() {
    User:: verifyLogin();
    $parcelas = new ParcelasPagas();



    try {

        $parcelas->setFk_id_contrato($_POST["numerocontrato"]);
        $parcelas->setDt_entrada_produto($_POST["dt_entrada_produto"]);
        $parcelas->setDt_saida_produto($_POST["dt_saida_produto"]);
        $parcelas->setDescricao_produto($_POST["descricao_produto"]);
        $parcelas->setMes_produto($_POST["mes_produto"]);
        $parcelas->setDt_inicio_evento($_POST["dt_inicio_evento"]);
        $parcelas->setDt_fim_evento($_POST["dt_fim_evento"]);
        $parcelas->setFonte_recurso($_POST["fonte_recurso"]);
        $parcelas->setValor_bruto($_POST["valor_bruto"]);
        $parcelas->setNotafiscal($_POST["notafiscal"]);
        $parcelas->setNomeevento($_POST["nomeevento"]);
        $parcelas->setNparticipantes($_POST["nparticipantes"]);
        $parcelas->setCategoria($_POST["categoria"]);
        $parcelas->setAvaliacao($_POST["avaliacao"]);
        $parcelas->setObservacao($_POST["observacao"]);
        $parcelas->setAcao($_POST["acao"]);
        $parcelas->setCentrodecusto($_POST["centrodecusto"]);

        $result = $parcelas->save();



        $notifica = Notificacao::notificacaoSaldo((int) $parcelas->getFk_id_contrato());
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header('Location: /contrato/' . $parcelas->getFk_id_contrato());

        exit;
    }

    header('Location: /contrato/' . $parcelas->getFk_id_contrato());
    exit;
});

$app->get('/produto/update/:idproduto/contrato/:idcontrato', function($idproduto, $idcontrato ) {
    User:: verifyLogin();
    $contrato = new Contratos();
    $contrato->get($idcontrato);
    $categoria = new Categoria();
    $avaliacao = new Avaliacao();
    $acao = new Acao();

    $produto = new ParcelasPagas();
    $produto->get($idproduto);

//codigo categoria
    $cat = $produto->getCategoria();
    if (!empty($cat)):
        $categoria->get($produto->getCategoria());

    endif;

    //codigo avaliação
    $avalia = $produto->getAvaliacao();
    if (!empty($avalia)):
        $avaliacao->get($avalia);
    endif;

    //codigo acao
    $codAcao = $produto->getAcao();
    if (!empty($codAcao)):
        $acao->get($codAcao);
    endif;

    $page = new PageAdmin();

    $page->setTpl("produto-update", array(
        "idproduto" => $produto->getId(),
        "idcontrato" => $contrato->getPk_di(),
        "contratado" => $contrato->getNome_razao(),
        "numero_contrato" => $contrato->getNumero_contrato(),
        "fontesRecursos" => fontesRecursos::listAll(),
        "dataEntrada" => $produto->getDt_entrada_produto(),
        "dataSaida" => $produto->getDt_saida_produto(),
        "descricao" => $produto->getDescricao_produto(),
        "mesProduto" => $produto->getMes_produto(),
        "inicioEvento" => $produto->getDt_inicio_evento(),
        "fimEvento" => $produto->getDt_fim_evento(),
        "codFonte" => $produto->getFonte_recurso(),
        "nomeFonte" => $produto->getNome_fonte(),
        "valor" => $produto->getValor_bruto(),
        "error" => StringError::getError(),
        "notafiscal" => $produto->getNotafiscal(),
        "nomeevento" => $produto->getNomeevento(),
        "nparticipantes" => $produto->getNparticipantes(),
        "categoria" => $categoria->getAll(),
        "nomeCategoria" => $categoria->getNo_categoria(),
        "idCategoria" => $categoria->getId_categoria(),
        "avaliacao" => $avaliacao->getAll(),
        "idAvaliacao" => $produto->getAvaliacao(),
        "noAvaliacao" => $avaliacao->getNo_avaliacao(),
        "observacao" => $produto->getObservacao(),
        "acao" => $acao->getAll(),
        "idAcao" => $produto->getAcao(),
        "noAcao" => $acao->getNo_acao(),
        "centrodecusto" => $produto->getCentrodecusto()
    ));
});
$app->post('/produto/update', function() {
    User:: verifyLogin();
    $parcelas = new ParcelasPagas();

    try {

        $parcelas->setId($_POST["idproduto"]);
//        $parcelas->setFk_id_contrato($_POST["numerocontrato"]);
//        $parcelas->setDt_entrada_produto($_POST["dt_entrada_produto"]);
//        $parcelas->setDt_saida_produto($_POST["dt_saida_produto"]);
//        $parcelas->setDescricao_produto($_POST["descricao_produto"]);
//        $parcelas->setMes_produto($_POST["mes_produto"]);
//        $parcelas->setDt_inicio_evento($_POST["dt_inicio_evento"]);
//        $parcelas->setDt_fim_evento($_POST["dt_fim_evento"]);
//        $parcelas->setFonte_recurso($_POST["fonte_recurso"]);
//        $parcelas->setValor_bruto($_POST["valor_bruto"]);

        $parcelas->setFk_id_contrato($_POST["numerocontrato"]);
        $parcelas->setDt_entrada_produto($_POST["dt_entrada_produto"]);
        $parcelas->setDt_saida_produto($_POST["dt_saida_produto"]);
        $parcelas->setDescricao_produto($_POST["descricao_produto"]);
        $parcelas->setMes_produto($_POST["mes_produto"]);
        $parcelas->setDt_inicio_evento($_POST["dt_inicio_evento"]);
        $parcelas->setDt_fim_evento($_POST["dt_fim_evento"]);
        $parcelas->setFonte_recurso($_POST["fonte_recurso"]);
        $parcelas->setValor_bruto($_POST["valor_bruto"]);
        $parcelas->setNotafiscal($_POST["notafiscal"]);
        $parcelas->setNomeevento($_POST["nomeevento"]);
        $parcelas->setNparticipantes($_POST["nparticipantes"]);
        $parcelas->setCategoria($_POST["categoria"]);
        $parcelas->setAvaliacao($_POST["avaliacao"]);
        $parcelas->setObservacao($_POST["observacao"]);
        $parcelas->setAcao($_POST["acao"]);
        $parcelas->setCentrodecusto($_POST["centrodecusto"]);

        $parcelas->update();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header('Location: /contrato/' . $parcelas->getFk_id_contrato());
        exit;
    }

    header('Location: /contrato/' . $parcelas->getFk_id_contrato());
    exit;
});
$app->get('/produto/delete/:idproduto/contrato/:idcontrato', function($idproduto, $idcontrato ) {
    User:: verifyLogin();
    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $produto = new ParcelasPagas();
    $produto->get($idproduto);

    $page = new PageAdmin();
    $page->setTpl("produto-delete", array(
        "idproduto" => $produto->getId(),
        "idcontrato" => $contrato->getPk_di(),
        "contratado" => $contrato->getNome_razao(),
        "numero_contrato" => $contrato->getNumero_contrato(),
        "fontesRecursos" => fontesRecursos::listAll(),
        "dataEntrada" => $produto->getDt_entrada_produto(),
        "dataSaida" => $produto->getDt_saida_produto(),
        "descricao" => $produto->getDescricao_produto(),
        "mesProduto" => $produto->getMes_produto(),
        "inicioEvento" => $produto->getDt_inicio_evento(),
        "fimEvento" => $produto->getDt_fim_evento(),
        "codFonte" => $produto->getFonte_recurso(),
        "nomeFonte" => $produto->getNome_fonte(),
        "valor" => $produto->getValor_bruto(),
        "error" => StringError::getError()
    ));
});
$app->post('/produto/delete', function() {
    User:: verifyLogin();

    $produto = new ParcelasPagas();
    try {

        $produto->setId($_POST["idproduto"]);
        $produto->setFk_id_contrato($_POST["numerocontrato"]);

        $produto->delete();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /contrato/" . $produto->getFk_id_contrato());
        exit;
    }
    header("Location: /contrato/" . $produto->getFk_id_contrato());
    exit;
});
/* =======================================================================
 *                                                                      *
 *                  ADITIVOS CONTRATO                                   *
 * ====================================================================== */

$app->get("/aditivo/create/:idcontrato", function($idcontrato) {

    User:: verifyLogin();
    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $page = new PageAdmin();

    $page->setTpl("aditivo-create", array(
        "idcontrato" => $contrato->getPk_di(),
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao()
    ));
});

$app->post("/aditivo/create", function() {

    User:: verifyLogin();

    try {

        $aditivo = new Aditivos();

        $aditivo->setFk_id_user((int) $_POST["idusuario"]);
        $aditivo->setFk_tb_contrato($_POST["idcontrato"]);
        $aditivo->setDt_inicio_ad($_POST["data_inicio"]);
        $aditivo->setDt_fim_ad($_POST["data_fim"]);
        $aditivo->setValor_ad($_POST["valor_total"]);

        $result = $aditivo->save();
    } catch (Exception $exc) {
        User::setError($e->getMessage());
        header('Location: /contrato/' . $aditivo->getFk_tb_contrato());

        exit;
    }
    header('Location: /contrato/' . $aditivo->getFk_tb_contrato());
    exit;
});

$app->get("/aditivo/update/:idaditivo/contrato/:idcontrato", function($idaditivo, $idcontrato) {

    User::verifyLogin();

    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $aditivo = new Aditivos();
    $aditivo->get($idaditivo);

    $page = new PageAdmin();

    $page->setTpl("aditivo-update", array(
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao(),
        "idcontrato" => $aditivo->getFk_tb_contrato(),
        "idaditivo" => $idaditivo,
        "datainicio" => $aditivo->getDt_inicio_ad(),
        "datafim" => $aditivo->getDt_fim_ad(),
        "valor" => $aditivo->getValor_ad(),
        "error" => User::getError()
    ));
});

$app->post("/aditivo/update", function() {

    User::verifyLogin();

    $aditivo = new Aditivos();
    try {

        $aditivo->setFk_tb_contrato($_POST["fk_tb_contrato"]);
        $aditivo->setFk_id((int) $_POST["idaditivo"]);
        $aditivo->setDt_inicio_ad($_POST["data_inicio"]);
        $aditivo->setDt_fim_ad($_POST["data_fim"]);
        $aditivo->setValor_ad($_POST["valor_total"]);
        $aditivo->setFk_id_user($_POST["idusuario"]);

        $aditivo->updateAditivo();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /contrato/" . $aditivo->getFk_tb_contrato());
        exit;
    }


    header("Location: /contrato/" . $aditivo->getFk_tb_contrato());
    exit;
});
$app->get("/aditivo/delete/:idaditivo/contrato/:idcontrato", function($idaditivo, $idcontrato) {

    User::verifyLogin();

    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $aditivo = new Aditivos();
    $aditivo->get($idaditivo);

    $page = new PageAdmin();

    $page->setTpl("aditivo-delete", array(
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao(),
        "idcontrato" => $aditivo->getFk_tb_contrato(),
        "idaditivo" => $idaditivo,
        "datainicio" => $aditivo->getDt_inicio_ad(),
        "datafim" => $aditivo->getDt_fim_ad(),
        "valor" => $aditivo->getValor_ad(),
        "error" => User::getError()
    ));
});

$app->post("/aditivo/delete", function() {

    User::verifyLogin();

    try {

        $aditivo = new Aditivos();
        $aditivo->setFk_id($_POST["idaditivo"]);

        $aditivo->setFk_tb_contrato($_POST["fk_tb_contrato"]);

        $aditivo->delete();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header("Location: /contrato/" . $aditivo->getFk_tb_contrato());
        exit;
    }

    header("Location: /contrato/" . $aditivo->getFk_tb_contrato());
    exit;
});

/* =======================================================================
 *                                                                      *
 *                  PARCELA CONTRATO                                   *
 * ====================================================================== */

$app->get("/parcela-create/:idcontrato", function($idcontrato) {

    User::verifyLogin();

    $contrato = new Contratos();

    $contrato->get($idcontrato);

    $page = new PageAdmin();

    $page->setTpl("parcela-create", array(
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao(),
        "idcontrato" => $contrato->getPk_di(),
        "valor" => $contrato->getValor_total(),
        "error" => User::getError()
    ));
});

$app->post("/parcela/create", function() {

    User::verifyLogin();
    $parcela = new Parcelas();

    try {

        $parcela->setData($_POST["data-parcela"]);
        $parcela->setValor_parcela($_POST["valor-parcela"]);
        $parcela->setFk_contrato($_POST["fk_contrato"]);
        $parcela->setContparcela((int) $_POST["confparcela"]);

        $parcela->save();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header("Location: /contrato/" . $parcela->getFk_contrato());
        exit;
    }
    header("Location: /contrato/" . $parcela->getFk_contrato());
    exit;
});

$app->get("/parcela/update/:idparcela/contrato/:idcontrato", function($idparcela, $idcontrato) {

    User::verifyLogin();

    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $parcela = new Parcelas();
    $parcela->getDataParcela($idparcela);

    $page = new PageAdmin();

    $page->setTpl("parcela-update", array(
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao(),
        "idparcela" => $parcela->getPk_id(),
        "idcontrato" => $contrato->getPk_di(),
        "dataparcela" => $parcela->getData(),
        "valor" => $parcela->getValor_parcela(),
        "error" => User::getError()
    ));
});

$app->post("/parcela/update", function() {

    User::verifyLogin();
    $parcela = new Parcelas();
    try {
        $parcela->setData($_POST["data_inicio"]);
        $parcela->setValor_parcela($_POST["valor_total"]);
        $parcela->setPk_id($_POST["idparcela"]);
        $parcela->setFk_contrato($_POST["fk_contrato"]);

        $parcela->update();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /contrato/" . $parcela->getFk_contrato());
        exit;
    }

    header("Location: /contrato/" . $parcela->getFk_contrato());
    exit;
});

$app->get("/parcela/delete/:idparcela/contrato/:idcontrato", function($idparcela, $idcontrato) {

    User::verifyLogin();

    $contrato = new Contratos();
    $contrato->get($idcontrato);

    $parcela = new Parcelas();
    $parcela->getDataParcela($idparcela);


    $page = new PageAdmin();

    $page->setTpl("parcela-delete", array(
        "numero_contrato" => $contrato->getNumero_contrato(),
        "contratado" => $contrato->getNome_razao(),
        "idparcela" => $parcela->getPk_id(),
        "idcontrato" => $contrato->getPk_di(),
        "dataparcela" => $parcela->getData(),
        "valor" => $parcela->getValor_parcela(),
        "error" => User::getError()
    ));
});

$app->post("/parcela/delete", function() {

    User::verifyLogin();
    $parcela = new Parcelas();
    try {

        $parcela->setPk_id($_POST["idparcela"]);
        $parcela->setFk_contrato($_POST["fk_contrato"]);

        $parcela->delete();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /contrato/" . $parcela->getFk_contrato());
        exit;
    }

    header("Location: /contrato/" . $parcela->getFk_contrato());
    exit;
});

/* =======================================================================
 *                                                                        *
 *                  Responsavel                                           *
 * ====================================================================== */

$app->get("/responsavel", function () {
    User::verifyLogin();
    $responsavel = Responsavel::ListAllResponsavel();

    $page = new PageAdmin();

    $page->setTpl("responsavel", array(
        "responsavel" => $responsavel,
        "error" => User::getError()
    ));
});
$app->get("/responsavel/create", function() {
    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("responsavel-create", array(
        "error" => User::getError()
    ));
});

$app->post("/responsavel/create/", function() {
    User::verifyLogin();

    $responsavel = new Responsavel();

    try {
        $responsavel->setNom_email($_POST["nome"]);
        $responsavel->setEmail($_POST["email"]);

        $responsavel->save();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header("Location: /responsavel");
        exit;
    }

    header("Location: /responsavel");
    exit;
});
$app->get("/responsavel/update/:idresponsavel", function($idresponsavel) {

    User::verifyLogin();

    $responsavel = new Responsavel();
    $responsavel->get($idresponsavel);

    $page = new PageAdmin();
    $page->setTpl("responsavel-update", array(
        "idresponsavel" => $responsavel->getPk_id(),
        "nomeresponsavel" => $responsavel->getNom_email(),
        "emailresponsavel" => $responsavel->getEmail(),
        "error" => User::getError()
    ));
});


$app->post("/responsavel/update/", function() {

    User::verifyLogin();
    $responsavel = new Responsavel();
    try {

        $responsavel->setPk_id($_POST["idresponsavel"]);
        $responsavel->setNom_email($_POST["nome"]);
        $responsavel->setEmail($_POST["email"]);

        $responsavel->update();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /responsavel");
        exit;
    }

    header("Location: /responsavel");
    exit;
});

/* =======================================================================
 *                                                                        *
 *                  Consultor                                             *
 * ====================================================================== */

$app->get("/consultor", function() {

    User::verifyLogin();
    $consultor = FornecedorConsultor::ListAllConsultor();

    $page = new PageAdmin();
    $page->setTpl("consultor", array(
        "consultor" => $consultor,
        "error" => User::getError()
    ));
});

$app->get("/consultor/create", function() {
    User::verifyLogin();

    $page = new PageAdmin();
    $page->setTpl("consultor-create", array(
        "error" => User::getError()
    ));
});

$app->post("/consultor/create", function() {

    $consultor = new FornecedorConsultor();

    try {

        $consultor->setNome_razao($_POST["nome"]);
        $consultor->setCpf($_POST["cpf"]);
        $consultor->setEndereco($_POST["endereco"]);
        $consultor->setCep($_POST["cep"]);
        $consultor->setTelefone($_POST["telefone"]);
        $consultor->setEmail_fc($_POST["email"]);
        $consultor->setDt_nascimento($_POST["dt_nascimento"]);
        $consultor->setRg($_POST["rg"]);

        $consultor->saveConsultor();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /consultor");
        exit;
    }

    header("Location: /consultor");
    exit;
});

$app->get("/consultor/:idconsultor", function($idconsultor) {

    $consultor = new FornecedorConsultor();
    $consultor->get($idconsultor);
    $page = new PageAdmin();
    $page->setTpl("consultor-view", array(
        "id" => $consultor->getPk_tb_id_fornecedor_consultorPrimária(),
        "nome" => $consultor->getNome_razao(),
        "cpf" => $consultor->getCpf(),
        "endereco" => $consultor->getEndereco(),
        "cep" => $consultor->getCep(),
        "telefone" => $consultor->getTelefone(),
        "email" => $consultor->getEmail_fc(),
        "dt_nascimento" => $consultor->getDt_nascimento(),
        "rg" => $consultor->getRg(),
        "error" => User::getError()
    ));
});
$app->get("/consultor/update/:idconsultor", function($idconsultor) {

    $consultor = new FornecedorConsultor();
    $consultor->get($idconsultor);
    $page = new PageAdmin();
    $page->setTpl("consultor-update", array(
        "id" => $consultor->getPk_tb_id_fornecedor_consultorPrimária(),
        "nome" => $consultor->getNome_razao(),
        "cpf" => $consultor->getCpf(),
        "endereco" => $consultor->getEndereco(),
        "cep" => $consultor->getCep(),
        "telefone" => $consultor->getTelefone(),
        "email" => $consultor->getEmail_fc(),
        "dt_nascimento" => $consultor->getDt_nascimento(),
        "rg" => $consultor->getRg(),
        "error" => User::getError()
    ));
});

$app->post("/consultor/update", function() {

    $consultor = new FornecedorConsultor();

    try {
        $consultor->setPk_tb_id_fornecedor_consultorPrimária($_POST["pk_tb_id_fornecedor_consultorPrimária"]);
        $consultor->setNome_razao($_POST["nome"]);
        $consultor->setCpf($_POST["cpf"]);
        $consultor->setEndereco($_POST["endereco"]);
        $consultor->setCep($_POST["cep"]);
        $consultor->setTelefone($_POST["telefone"]);
        $consultor->setEmail_fc($_POST["email"]);
        $consultor->setDt_nascimento($_POST["dt_nascimento"]);
        $consultor->setRg($_POST["rg"]);

        $consultor->update();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /consultor/" . $consultor->getPk_tb_id_fornecedor_consultorPrimária());
        exit;
    }

    header("Location: /consultor/" . $consultor->getPk_tb_id_fornecedor_consultorPrimária());
    exit;
});

/* =======================================================================
 *                                                                        *
 *                  Fornecedor                                            *
 * ====================================================================== */

$app->get("/fornecedor", function() {

    User::verifyLogin();
    $fornecedor = FornecedorConsultor::ListAllFornecedor();

    $page = new PageAdmin();
    $page->setTpl("fornecedor", array(
        "fornecedor" => $fornecedor,
        "error" => User::getError()
    ));
});

$app->get("/fornecedor/create", function() {

    User::verifyLogin();

    $listAtividades = Atividades::getAll();
    $page = new PageAdmin();
    $page->setTpl("fornecedor-create", array(
        "listAtividade" => $listAtividades,
        "error" => User::getError()
    ));
});

$app->post("/fornecedor/create", function() {

    User::verifyLogin();

    $fornecedor = new FornecedorConsultor();

    try {

        $fornecedor->setNome_razao($_POST["razao_social"]);
        $fornecedor->setNome_fantasia($_POST["nome_fantasia"]);
        $fornecedor->setContato($_POST["contato"]);
        $fornecedor->setCnpj($_POST["cnpj"]);
        $fornecedor->setEndereco($_POST["endereco"]);
        $fornecedor->setCep($_POST["cep"]);
        $fornecedor->setEmail_fc($_POST["email"]);
        $fornecedor->setTelefone($_POST["telefone"]);
        $fornecedor->setFk_tipo_atividade($_POST["tipo_atividade"]);

        $fornecedor->saveFornecedor();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /fornecedor");
        exit;
    }
    header("Location: /fornecedor");
    exit;
});

$app->get("/fornecedor/:idfornecedor", function($idfornecedor) {
    User::verifyLogin();
    $fornecedor = new FornecedorConsultor();
    $fornecedor->getFornecedor($idfornecedor);
    $page = new PageAdmin();
    $page->setTpl("fornecedor-view", array(
        "id" => $fornecedor->getPk_tb_id_fornecedor_consultorPrimária(),
        "razao" => $fornecedor->getNome_razao(),
        "fantasia" => $fornecedor->getNome_fantasia(),
        "contato" => $fornecedor->getContato(),
        "cnpj" => $fornecedor->getCnpj(),
        "endereco" => $fornecedor->getEndereco(),
        "cep" => $fornecedor->getCep(),
        "email" => $fornecedor->getEmail_fc(),
        "telefone" => $fornecedor->getTelefone(),
        "atividade" => $fornecedor->getNo_atividade(),
        "error" => User::getError()
    ));
});
$app->get("/fornecedor/update/:idfornecedor", function($idfornecedor) {
    User::verifyLogin();
    $fornecedor = new FornecedorConsultor();
    $fornecedor->getFornecedor($idfornecedor);
    $listAtividades = Atividades::getAll();
    $page = new PageAdmin();
    $page->setTpl("fornecedor-update", array(
        "id" => $fornecedor->getPk_tb_id_fornecedor_consultorPrimária(),
        "razao" => $fornecedor->getNome_razao(),
        "fantasia" => $fornecedor->getNome_fantasia(),
        "contato" => $fornecedor->getContato(),
        "cnpj" => $fornecedor->getCnpj(),
        "endereco" => $fornecedor->getEndereco(),
        "cep" => $fornecedor->getCep(),
        "email" => $fornecedor->getEmail_fc(),
        "telefone" => $fornecedor->getTelefone(),
        "atividade" => $fornecedor->getNo_atividade(),
        "codAtividade" => $fornecedor->getFk_tipo_atividade(),
        "listAtividade" => $listAtividades,
        "error" => User::getError()
    ));
});

$app->post("/fornecedor/update", function() {
    User::verifyLogin();
    $fornecedor = new FornecedorConsultor();
    try {
        $fornecedor->setPk_tb_id_fornecedor_consultorPrimária($_POST["id_registro"]);
        $fornecedor->setNome_razao($_POST["razao_social"]);
        $fornecedor->setNome_fantasia($_POST["nome_fantasia"]);
        $fornecedor->setContato($_POST["contato"]);
        $fornecedor->setCnpj($_POST["cnpj"]);
        $fornecedor->setEndereco($_POST["endereco"]);
        $fornecedor->setCep($_POST["cep"]);
        $fornecedor->setEmail_fc($_POST["email"]);
        $fornecedor->setTelefone($_POST["telefone"]);
        $fornecedor->setFk_tipo_atividade($_POST["tipo_atividade"]);

        $fornecedor->updateFornecedor();
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header("Location: /fornecedor/" . $fornecedor->getPk_tb_id_fornecedor_consultorPrimária());
        exit;
    }
    header("Location: /fornecedor");
    exit;
});

/* =======================================================================
 *                                                                        *
 *                  Categoria                                             *
 * ====================================================================== */

$app->get("/categoria", function() {
    User::verifyLogin();
    $categoria = Categoria::getAll();
    $page = new PageAdmin();

    $page->setTpl("categoria", array(
        "categoria" => $categoria,
        "error" => User::getError()
    ));
});

$app->get("/categoria/create", function() {
    User::verifyLogin();
    $page = new PageAdmin();

    $page->setTpl("categoria-create", array(
        "error" => User::getError()
    ));
});
$app->post("/categoria/create", function() {

    User::verifyLogin();

    $categoria = new Categoria();

    try {

        $categoria->setNo_categoria($_POST["no_categoria"]);
        $categoria->setId_usuario($_POST["idusuario"]);
        $categoria->save();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header("Location: /categoria");
        exit;
    }

    header("Location: /categoria");
    exit;
});

$app->get("/categoria/update/:id", function($id) {

    User::verifyLogin();

    $categoria = new Categoria();
    $categoria->get($id);

    $page = new PageAdmin();

    $page->setTpl("categoria-update", array(
        "id_categoria" => $categoria->getId_categoria(),
        "no_categoria" => $categoria->getNo_categoria(),
        "error" => User::getError()
    ));
});
$app->post("/categoria/update", function() {

    User::verifyLogin();

    $categoria = new Categoria();

    try {

        $categoria->setId_categoria($_POST["id_categoria"]);
        $categoria->setId_usuario($_POST["idusuario"]);
        $categoria->setNo_categoria($_POST["no_categoria"]);

        $categoria->update();
    } catch (Exception $e) {

        User::setError($e->getMessage());
        header("Location: /categoria");
        exit;
    }

    header("Location: /categoria");
    exit;
});

$app->run();
?>