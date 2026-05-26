<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
    header("Location: ./clientes.php");
    exit();
}
require_once("menu.php");
?>
<h4 class="align-items-center d-flex justify-content-between mb-4 text-muted text-uppercase">
    LISTAR FILMES
    <div class="d-flex gap-2">
        <button type="button" onclick="buscarTMDBPeloNomeDoFilme()" class="btn btn-outline-success">Puxar TMDB</button>
        <button type="button" onclick="atualizarDetalhesTMDB()" class="btn btn-outline-success">Puxar Info</button>
        <button type="button" class="btn btn-outline-success fa-plus fas"
                onclick='modal_master("api/filmes.php", "adicionar_filmes", "add")'></button>
    </div>
</h4>

<table id="data_table" class="display overflow-auto table" style="width: 100%;">
    <thead class="table-dark">
    <tr><!--<th></th> descomentar para usar childs -->
        <th style="min-width: 75px;">#</th>
        <th>Nome</th>
        <th>Icon</th>
        <th>Categoria</th>
        <th>Tipo</th>
        <th style="font-size: small;">Adulto</th>
        <th style="min-width: 191px;">Ações</th>
    </tr>
    </thead>
</table>


<script src="//cdn.datatables.net/2.0.7/js/dataTables.js"></script>
<script src="./js/sweetalert2.js"></script>
<script src="./js/datatablevod.js?sfd"></script>
<script src="./js/custom.js"></script>

</div>
</main>

<!-- Modal master -->
<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true"
     style="backdrop-filter: blur(5px) grayscale(1);">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="d-block modal-header" id="modal_master-header">
                <h5 class="float-start modal-title" id="modal_master-titulo"></h5>
                <button type="button" class="fa btn text-white fa-close fs-6 float-end" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </button>
            </div>
            <form id="modal_master_form" onsubmit="event.preventDefault();" autocomplete="off">
                <div id="modal_master-body" class="modal-body overflow-auto"></div>
                <div id="modal_master-footer" class="modal-footer"></div>
            </form>
        </div>
    </div>
</div>
<!-- Modal master Fim-->

<div id="modalProgresso"
     style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
     background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">

    <div style="background: #fff; padding: 20px 20px 30px; border-radius: 10px; width: 420px; max-width: 90%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3); position: relative; font-family: Arial, sans-serif;">

        <!-- Botao de fechar -->
        <button onclick="fecharModalProgresso()"
                style="position: absolute; top: 10px; right: 10px; background: transparent; border: none;
                font-size: 20px; cursor: pointer;">✖
        </button>

        <h3 style="margin: 0 0 20px; text-align: center; font-size: 20px; color: #333;">
            Atualizando Filmes
        </h3>

        <!-- Barra de progresso -->
        <div style="background: #eee; border-radius: 6px; height: 25px; margin-bottom: 15px; overflow: hidden;">
            <div id="barraProgresso"
                 style="height: 100%; width: 0%; background: linear-gradient(to right, #28a745, #43d77d);
                 text-align: center; color: white; line-height: 25px; font-weight: bold; transition: width 0.4s;">
                0 / 0
            </div>
        </div>

        <!-- Lista de progresso -->
        <div id="progressoLista"
             style="max-height: 200px; overflow-y: auto; font-size: 14px; border: 1px solid #ccc; padding: 10px;
             border-radius: 5px; background: #f9f9f9;">
            <!-- Itens de progresso serao add aqui -->
        </div>

        <!-- Botao de cancelar -->
        <button onclick="cancelModalProgresso()"
                style="margin-top: 20px; width: 100%; padding: 10px; background: #dc3545; color: white;
                border: none; border-radius: 5px; cursor: pointer; font-size: 15px;">
            Cancelar Atualização
        </button>
    </div>
</div>


<div id="tmdbProgressModal"
     style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background-color:rgba(0,0,0,0.7); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:30px 20px 20px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.3); width:320px; text-align:center; position:relative;">
        <button onclick="fecharTMDBModal()"
                style="position:absolute; top:10px; right:10px; background:transparent; border:none; font-size:18px; cursor:pointer;">
            ✖
        </button>
        <h3 id="tmdbStatus" style="margin-bottom:20px;">Iniciando atualização...</h3>
        <div style="height:20px; background:#eee; border-radius:10px; overflow:hidden; box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
            <div id="tmdbProgressBar"
                 style="height:100%; width:0%; background:linear-gradient(to right, #4caf50, #81c784); transition:width 0.3s ease;"></div>
        </div>
        <button onclick="fecharTMDBModal()"
                style="margin-top:15px; background:#e53935; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">
            Cancelar
        </button>
    </div>
</div>


<script>


    function fecharModalProgresso() {
        document.getElementById('modalProgresso').style.display = 'none';
        cancelarAtualizacao = true;
    }

    function cancelModalProgresso() {
        document.getElementById('modalProgresso').style.display = 'none';
        cancelarAtualizacao = true;
    }


    let cancelarAtualizacao = false;

    function fecharTMDBModal() {
        document.getElementById('tmdbProgressModal').style.display = 'none';
        cancelarAtualizacao = true;
    }

    async function atualizarDetalhesTMDB() {
    cancelarAtualizacao = false;
    document.getElementById('tmdbProgressModal').style.display = 'block';

    const response = await fetch('api/tmdb.php?action=atualizar_infos');
    const filmes = await response.json();
    const total = filmes.length;

    const blockSize = 200;
    let startIndex = parseInt(localStorage.getItem('atualizacao_index')) || 0;

    async function processarBloco() {
        if (cancelarAtualizacao || startIndex >= total) {
            document.getElementById('tmdbStatus').innerText =
                cancelarAtualizacao ? '❌ Atualização cancelada' : `✅ Todos os filmes foram atualizados!`;
            localStorage.removeItem('atualizacao_index');
            return;
        }

        const bloco = filmes.slice(startIndex, startIndex + blockSize);
        let atual = 0;

        function delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        for (const filme of bloco) {
            if (cancelarAtualizacao) {
                document.getElementById('tmdbStatus').innerText = '❌ Atualização cancelada';
                return;
            }

            const tmdbId = filme.tmdb_id;
            const localId = filme.id;

            try {
                const data = await fetch(`api/tmdb.php?get_filmes=${tmdbId}`);
                const info = await data.json();

                await fetch('api/tmdb.php?action=salvar_detalhes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        id: localId,
                        titulo: info.nome,
                        description: info.plot,
                        plot: info.plot,
                        stream_icon: info.logo,
                        backdrop_path: info.backdrop_path,
                        release_date: info.releasedate,
                        duration: info.duration,
                        duration_secs: 0,
                        rating: info.rating,
                        rating_5based: info.rating_5based,
                        age: info.adult ? '18+' : 'Livre',
                        year: info.year ? info.year : 'Livre',
                        genre: info.genre,
                        actors: info.actors,
                        country: '',
                        director: info.director,
                        runtime: info.runtime,
                        youtube_trailer: ''
                    })
                });

                atual++;
                const globalIndex = startIndex + atual;
                const percent = ((globalIndex / total) * 100).toFixed(1);

                document.getElementById('tmdbStatus').innerText = `Atualizando ${globalIndex} de ${total}...`;
                document.getElementById('tmdbProgressBar').style.width = percent + '%';

                localStorage.setItem('atualizacao_index', globalIndex);
                await delay(300);

            } catch (e) {
                console.error(`Erro ao processar TMDb ID ${tmdbId}`, e);
            }
        }

        startIndex += atual;
        await processarBloco();
    }

    await processarBloco();
}

    async function buscarTMDBPeloNomeDoFilme() {
        const modal = document.getElementById('modalProgresso');
        const barra = document.getElementById('barraProgresso');
        const lista = document.getElementById('progressoLista');

        modal.style.display = 'flex';
        lista.innerHTML = '';
        barra.style.width = '0%';
        barra.innerText = '0 / 0';

        try {
            const res = await fetch('api/tmdb.php?action=listar');
            const filmes = await res.json();

            const total = filmes.length;
            let atual = 0;

            for (const filme of filmes) {
                const linha = document.createElement('div');
                linha.textContent = `🔄 ${filme.name}`;
                lista.appendChild(linha);

                try {
                    const tmdb_id = await buscarTMDBId(filme.name);
                    if (tmdb_id) {
                        await fetch('api/tmdb.php?action=atualizar', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `id=${filme.id}&tmdb_id=${tmdb_id}`
                        });
                        linha.textContent = `✅ ${filme.name} → TMDB ID: ${tmdb_id}`;
                        linha.style.color = 'green';
                    } else {
                        linha.textContent = `❌ ${filme.name} não encontrado`;
                        linha.style.color = 'red';
                    }
                } catch (e) {
                    linha.textContent = `⚠️ Erro ao buscar ${filme.name}`;
                    linha.style.color = 'orange';
                }

                atual++;
                const perc = Math.round((atual / total) * 100);
                barra.style.width = perc + '%';
                barra.innerText = `${atual} / ${total}`;
            }

            const final = document.createElement('div');
            final.innerHTML = `<strong>🎉 Processamento concluído: ${total} filmes</strong>`;
            lista.appendChild(final);
        } catch (erro) {
            lista.innerHTML = '<div style="color: red;">Erro ao buscar lista de filmes.</div>';
        }
    }
 
    function limparNome(nome) {
  nome = nome.replace(/\(.*?\)/g, '');
  nome = nome.replace(/\[.*?\]/g, '');
  nome = nome.replace(/\b(19|20)\d{2}\b/g, '');
  nome = nome.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  nome = nome.replace(/[^a-zA-Z0-9\s]/g, '');
  nome = nome.replace(/\b(LEG|Leg|leg|Legendado)\b/g, '');
  nome = nome.replace(/\[LEG\]/gi, '');
  nome = nome.replace(/\(LEG\)/gi, '');
  nome = nome.replace(/\(\d{4}\)/g, '');
  nome = nome.replace(/\s\d{4}$/, '');
  nome = nome.replace(/\b4K\b/gi, '');
  nome = nome.replace(/\s+/g, ' ').trim();
  return nome.toLowerCase();
}

function extrairAno(nome) {
  const anoMatch = nome.match(/\b(19|20)\d{2}\b/);
  return anoMatch ? anoMatch[0] : null;
}

async function buscarTMDBId(nomeOriginal) {
  const chave = '66d600a2e10bb528752724cddadf6f8c';
  const nomeFiltrado = limparNome(nomeOriginal);
  const anoExtraido = extrairAno(nomeOriginal);

  const url = `https://api.themoviedb.org/3/search/movie?api_key=${chave}&query=${encodeURIComponent(nomeFiltrado)}&language=pt-BR&include_adult=true`;
  const res = await fetch(url);
  const json = await res.json();
  const resultados = json.results || [];

  if (anoExtraido) {
    const exatoComAno = resultados.find(r => {
      const nomeTMDB = limparNome(r.title || '');
      return (nomeTMDB === nomeFiltrado || limparNome(r.original_title || '') === nomeFiltrado) &&
             r.release_date?.startsWith(anoExtraido);
    });
    if (exatoComAno) return exatoComAno.id;
  }

  const exatoSemAno = resultados.find(r => {
    const nomeTMDB = limparNome(r.title || '');
    return nomeTMDB === nomeFiltrado || limparNome(r.original_title || '') === nomeFiltrado;
  });
  if (exatoSemAno) return exatoSemAno.id;

  if (anoExtraido) {
    const parcialComAno = resultados.find(r => {
      const nomeTMDB = limparNome(r.title || '');
      return (nomeTMDB.includes(nomeFiltrado) || limparNome(r.original_title || '').includes(nomeFiltrado)) &&
             r.release_date?.startsWith(anoExtraido);
    });
    if (parcialComAno) return parcialComAno.id;
  }

  return resultados[0]?.id ?? null;
}
</script>
</body>
</html>