<?php
require "config.php";
$api = $CONFIG['api'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Editor</title>

<style>
body { margin:0; display:flex; height:100vh; font-family:monospace; }

#lateral { width:250px; background:#222; color:#fff; padding:5px; overflow:auto; }
.item { padding:5px; cursor:pointer; }
.item:hover { background:#444; }
.selecionado { background:#555; }

#editor { flex:1; display:flex; flex-direction:column; }

#topo { background:#ddd; padding:5px; font-size:13px; }
textarea { flex:1; border:none; outline:none; padding:10px; font-family:monospace; }

.info { margin-left:10px; }
</style>
</head>

<body>

<div id="lateral"></div>

<div id="editor">
    <div id="topo">
        <button onclick="guardar()">guardar</button>
        <button onclick="download()">download</button>
        <button onclick="eliminar()">eliminar</button>

        <input type="file" id="uploadInput">
        <button onclick="upload()">upload</button>

        <span class="info">📂 <span id="caminho"></span></span>
        <span class="info">📄 <span id="ficheiro"></span></span>
        <span id="msg" style="margin-left:15px; color:green;"></span>
    </div>

    <textarea id="txt"></textarea>
</div>

<script>
const API = "<?php echo $api; ?>";

let pastaAtual = "C:/teste";
let ficheiroAtual = "";
let selecionado = null;

//pedidos
function pedir(url, opcoes = {}) {
    return fetch(url, opcoes)
    .then(r => r.text())
    .then(res => {
        try {
            return JSON.parse(res);
        } catch(e) {
            alert("Erro:\n" + res);
            throw e;
        }
    });
}

//lista 
function listar(pasta) {
    pastaAtual = pasta;
    ficheiroAtual = "";

    document.getElementById("txt").value = "";
    document.getElementById("caminho").textContent = pasta;
    document.getElementById("ficheiro").textContent = "";

    pedir(API + "?acao=listar&pasta=" + encodeURIComponent(pasta))
    .then(lista => {
        let lateral = document.getElementById("lateral");
        lateral.innerHTML = "";

        if (pasta !== "C:/teste") {
            let partes = pasta.split("/").filter(p => p !== "");
            partes.pop();
            let voltar = partes.length ? partes.join("/") + "/" : "C:/teste";

            let div = document.createElement("div");
            div.className = "item";
            div.textContent = "⬅️ ..";
            div.onclick = function() { listar(voltar); };

            lateral.appendChild(div);
        }

        lista.forEach(i => {
            let div = document.createElement("div");
            div.className = "item";

            if (i.tipo === "pasta") {
                div.textContent = "📁 " + i.nome;
                div.onclick = function() {
                    listar(pasta + "/" + i.nome);
                };
            } else {
                div.textContent = "📄 " + i.nome;
                div.onclick = function() {
                    abrir(pasta + "/" + i.nome);

                    if (selecionado) {
                        selecionado.classList.remove("selecionado");
                    }

                    div.classList.add("selecionado");
                    selecionado = div;
                };
            }

            lateral.appendChild(div);
        });
    });
}

//abrir
function abrir(f) {
    ficheiroAtual = f;
    document.getElementById("ficheiro").textContent = f;

    pedir(API + "?acao=abrir&ficheiro=" + encodeURIComponent(f))
    .then(d => {
        document.getElementById("txt").value = d.conteudo || "";
    });
}

//save
function guardar() {
    if (!ficheiroAtual) return alert("abre ficheiro");

    if (!confirm("Tens a certeza que queres guardar?")) return;

    pedir(API + "?acao=guardar", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({
            ficheiro: ficheiroAtual,
            conteudo: document.getElementById("txt").value
        })
    }).then(() => mostrarMsg("guardado com sucesso"));
}

//download
function download() {
    if (!ficheiroAtual) {
        return alert("seleciona um ficheiro");
    }

    window.open(API + "?acao=download&ficheiro=" + encodeURIComponent(ficheiroAtual));
}

//upload
function upload() {
    const f = document.getElementById("uploadInput").files[0];
    if (!f) return alert("escolhe ficheiro");

    if (!confirm("Queres fazer upload deste ficheiro?")) return;

    let form = new FormData();
    form.append("ficheiro", f);

    fetch(API + "?acao=upload", {
        method: "POST",
        body: form
    })
    .then(r => r.text())
    .then(res => {
        try {
            let json = JSON.parse(res);
            if (json.erro) return alert(json.erro);

            mostrarMsg("upload feito");
            listar(pastaAtual);
        } catch(e) {
            alert("erro upload:\n" + res);
        }
    });
}

function eliminar() {
    if (!ficheiroAtual) return alert("seleciona um ficheiro");

    if (!confirm("Tens a certeza que queres eliminar este ficheiro?")) return;

    pedir(API + "?acao=eliminar&ficheiro=" + encodeURIComponent(ficheiroAtual))
    .then(res => {
        if (res.erro) return alert(res.erro);

        mostrarMsg("ficheiro eliminado");
        listar(pastaAtual);
    });
}

function mostrarMsg(texto) {
    const msg = document.getElementById("msg");
    msg.textContent = texto;

    setTimeout(() => {
        msg.textContent = "";
    }, 3000);
}


// iniciar
listar(pastaAtual);
</script>

</body>
</html>