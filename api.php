<?php
require "config.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$raiz = $CONFIG['raiz'];
$raiz = rtrim($raiz, "/\\");

function seguro($base, $caminho) {
    $real = realpath($caminho);
    if ($real && strpos($real, realpath($base)) === 0) return $real;
    return false;
}

//mostrar
if(isset($_GET['acao']) && $_GET['acao'] == "listar") {
    $pasta = $_GET['pasta'] ?? $raiz;
    $caminho = seguro($raiz, $pasta);

    if (!$caminho) {
        echo json_encode(["erro"=>"acesso negado"]);
        exit;
    }

    $res = [];
    foreach (scandir($caminho) as $i) {
        if ($i == "." || $i == "..") continue;

        $p = $caminho . "/" . $i;

        $res[] = [
            "nome"=>$i,
            "tipo"=> is_dir($p) ? "pasta" : "ficheiro"
        ];
    }

    echo json_encode($res);
    exit;
}

//abrir
if(isset($_GET['acao']) && $_GET['acao'] == "abrir") {
    $caminho = seguro($raiz, $_GET['ficheiro']);

    echo json_encode([
        "conteudo"=> file_get_contents($caminho)
    ]);
    exit;
}

//save
if(isset($_GET['acao']) && $_GET['acao'] == "guardar") {
    $dados = json_decode(file_get_contents("php://input"), true);

    $caminho = seguro($raiz, $dados['ficheiro']);

    file_put_contents($caminho, $dados['conteudo']);

    echo json_encode(["ok"=>1]);
    exit;
}

// download
if(isset($_GET['acao']) && $_GET['acao'] == "download") {
    $caminho = seguro($raiz, $_GET['ficheiro']);

    if (!$caminho || !is_file($caminho)) {
        http_response_code(404);
        exit;
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . basename($caminho) . "\"");
    header("Content-Length: " . filesize($caminho));

    readfile($caminho);
    exit;
}


//upload
if(isset($_GET['acao']) && $_GET['acao'] == "upload") {

    if (!isset($_FILES['ficheiro'])) {
        echo json_encode(["erro"=>"sem ficheiro"]);
        exit;
    }

    $nome = basename($_FILES['ficheiro']['name']);
    $destino = $raiz . DIRECTORY_SEPARATOR . $nome;

    if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $destino)) {
        echo json_encode(["ok"=>1, "metodo"=>"move"]);
        exit;
    }

    if (copy($_FILES['ficheiro']['tmp_name'], $destino)) {
        echo json_encode(["ok"=>1, "metodo"=>"copy"]);
        exit;
    }

    echo json_encode([
        "erro"=>"falha upload",
        "tmp"=>$_FILES['ficheiro']['tmp_name'],
        "destino"=>$destino
    ]);
    exit;
}

if(isset($_GET['acao']) && $_GET['acao'] == "eliminar") {
    $caminho = seguro($raiz, $_GET['ficheiro']);

    if (!$caminho || !file_exists($caminho)) {
        echo json_encode(["erro"=>"ficheiro não existe"]);
        exit;
    }

    if (unlink($caminho)) {
        echo json_encode(["ok"=>1]);
    } else {
        echo json_encode(["erro"=>"erro ao eliminar"]);
    }

    exit;
}
?>