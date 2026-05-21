<?php
session_start();
if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
    header("Location: ./clientes.php");
    exit();
}
require_once("menu.php");
?>
<h4 class="align-items-center d-flex justify-content-between mb-4 text-muted text-uppercase">
    LISTAR SERIES
    <div class="d-flex gap-2">
      <button type="button" onclick="buscarTMDBPeloNomeDaSerie()" class="btn btn-outline-success">Puxar TMDB</button>
      <button type="button" onclick="atualizarDetalhesTMDB()" class="btn btn-outline-success">Puxar Info</button>
      <button type="button" class="btn btn-outline-success fa-plus fas" onclick='modal_master("api/series.php", "adicionar_series", "add")'></button>
    </div>
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
<script src="./js/datatableseries.js?sfd"></script>
<script src="./js/custom.js"></script>

</div>
</main>

<!-- Modal master -->
<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content">
      <div class="d-block modal-header" id="modal_master-header">
        <h5 class="float-start modal-title" id="modal_master-titulo"></h5>
        <button type="button" class="fa btn text-white fa-close fs-6 float-end" data-bs-dismiss="modal" aria-label="Close"></button>
        </button>
      </div>
      <form id="modal_master_form" onsubmit="event.preventDefault();" autocomplete="off">
        <div id="modal_master-body" class="modal-body overflow-auto" style="max-height: 421px;"></div>
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

        <button onclick="fecharModalProgresso()"
                style="position: absolute; top: 10px; right: 10px; background: transparent; border: none;
                font-size: 20px; cursor: pointer;">✖
        </button>

        <h3 style="margin: 0 0 20px; text-align: center; font-size: 20px; color: #333;">
            Atualizando Series
        </h3>

        <div style="background: #eee; border-radius: 6px; height: 25px; margin-bottom: 15px; overflow: hidden;">
            <div id="barraProgresso"
                 style="height: 100%; width: 0%; background: linear-gradient(to right, #28a745, #43d77d);
                 text-align: center; color: white; line-height: 25px; font-weight: bold; transition: width 0.4s;">
                0 / 0
            </div>
        </div>

        <div id="progressoLista"
             style="max-height: 200px; overflow-y: auto; font-size: 14px; border: 1px solid #ccc; padding: 10px;
             border-radius: 5px; background: #f9f9f9;">
        </div>

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

    const response = await fetch('api/tmdb_series.php?action=atualizar_infos');
    if (!response.ok) throw new Error('Falha ao buscar séries');

    const series = await response.json();
    const total = series.length;
    let atual = 0;
    const blocoTamanho = 50;

    async function processarBloco(inicio) {
        const fim = Math.min(inicio + blocoTamanho, total);
        const bloco = series.slice(inicio, fim);

        for (const serie of bloco) {
            if (cancelarAtualizacao) {
                document.getElementById('tmdbStatus').innerText = `❌ Atualização cancelada`;
                return;
            }

            const tmdbId = serie.tmdb_id;
            const localId = serie.id;

            const data = await fetch(`api/tmdb_series.php?get_series=${tmdbId}`);
            const info = await data.json();

            await fetch('api/tmdb_series.php?action=salvar_detalhes', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    id: localId,
                    titulo: info.nome,
                    plot: info.plot,
                    cover: info.logo,
                    backdrop_path: info.backdrop_path,
                    release_date: info.releasedate,
                    rating: info.rating,
                    rating_5based: info.rating_5based,
                    year: info.year ? info.year : 'Livre',
                    cast: info.cast,
                    genre: info.genre,
                    director: info.director,
                    youtube_trailer: ''
                })
            });

            atual++;
            const percent = ((atual / total) * 100).toFixed(1);
            document.getElementById('tmdbStatus').innerText = `Atualizando ${atual} de ${total}...`;
            document.getElementById('tmdbProgressBar').style.width = percent + '%';
        }

        // Processar próximo bloco
        if (fim < total && !cancelarAtualizacao) {
            await new Promise(resolve => setTimeout(resolve, 200)); // pequena pausa entre blocos
            await processarBloco(fim);
        }
    }

    await processarBloco(0);

    if (!cancelarAtualizacao) {
        document.getElementById('tmdbStatus').innerText = `✅ Finalizado: ${total} séries atualizadas`;
    }
}

    async function buscarTMDBPeloNomeDaSerie() {
        const modal = document.getElementById('modalProgresso');
        const barra = document.getElementById('barraProgresso');
        const lista = document.getElementById('progressoLista');

        modal.style.display = 'flex';
        lista.innerHTML = '';
        barra.style.width = '0%';
        barra.innerText = '0 / 0';

        try {
            const res = await fetch('api/tmdb_series.php?action=listar');
            const series = await res.json();

            const total = series.length;
            let atual = 0;

            for (const serie of series) {
                const linha = document.createElement('div');
                linha.textContent = `🔄 ${serie.name}`;
                lista.appendChild(linha);

                try {
                    const tmdb_id = await buscarTMDBId(serie.name);
                    if (tmdb_id) {
                        await fetch('api/tmdb_series.php?action=atualizar', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `id=${serie.id}&tmdb_id=${tmdb_id}`
                        });
                        linha.textContent = `✅ ${serie.name} → TMDB ID: ${tmdb_id}`;
                        linha.style.color = 'green';
                    } else {
                        linha.textContent = `❌ ${serie.name} não encontrado`;
                        linha.style.color = 'red';
                    }
                } catch (e) {
                    linha.textContent = `⚠️ Erro ao buscar ${serie.name}`;
                    linha.style.color = 'orange';
                }

                atual++;
                const perc = Math.round((atual / total) * 100);
                barra.style.width = perc + '%';
                barra.innerText = `${atual} / ${total}`;
            }

            const final = document.createElement('div');
            final.innerHTML = `<strong>🎉 Processamento concluído: ${total} series</strong>`;
            lista.appendChild(final);
        } catch (erro) {
            lista.innerHTML = '<div style="color: red;">Erro ao buscar lista de series.</div>';
        }
    }


    function limparNome(nome) {
nome = nome.replace(/\(.*?\)/g, '');
nome = nome.replace(/\[.*?\]/g, '');
nome = nome.replace(/\b(19|20)\d{2}\b/g, '');
nome = nome.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
nome = nome.replace(/[^a-zA-Z0-9\s]/g, '');
nome = nome.replace(/\s+/g, ' ').trim();
return nome.toLowerCase();
}

async function buscarTMDBId(nomeOriginal) {
const chave = '66d600a2e10bb528752724cddadf6f8c';
const nomeFiltrado = limparNome(nomeOriginal);

const url = `https://api.themoviedb.org/3/search/tv?api_key=${chave}&query=${encodeURIComponent(nomeFiltrado)}&language=pt-BR&include_adult=true`;

const res = await fetch(url);
const json = await res.json();
const resultados = json.results || [];

const exato = resultados.find(r =>
 limparNome(r.name || '') === nomeFiltrado ||
 limparNome(r.original_name || '') === nomeFiltrado
 );
if (exato) return exato.id;

 const parcial = resultados.find(r =>
 limparNome(r.name || '').includes(nomeFiltrado) ||
 limparNome(r.original_name || '').includes(nomeFiltrado)
);
if (parcial) return parcial.id;

return resultados[0]?.id ?? null;
}
</script>
</body>
</html>