<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Conteúdo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Adicionar Filme ou Série do TMDB</h2>
        <div class="input-group mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Digite o nome do filme ou série...">
            <button class="btn btn-primary" onclick="searchTmdb()">Buscar</button>
        </div>

        <h4>Resultados:</h4>
        <div id="results" class="row">
            </div>
    </div>

    <script>
    async function searchTmdb() {
        const query = document.getElementById('searchInput').value;
        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '<p>Buscando...</p>';

        // Chama nosso script de backend (api_tmdb.php)
        const response = await fetch(`api_tmdb.php?type=movie&query=${query}`);
        const data = await response.json();

        resultsDiv.innerHTML = '';
        if (data.results) {
            data.results.forEach(movie => {
                const posterPath = movie.poster_path ? `https://image.tmdb.org/t/p/w200${movie.poster_path}` : 'https://via.placeholder.com/200x300';
                // Este é um exemplo simples. O ideal é criar um formulário
                // ao clicar em um filme para adicionar o link do stream e salvar.
                resultsDiv.innerHTML += `
                    <div class="col-md-3">
                        <div class="card">
                            <img src="${posterPath}" class="card-img-top">
                            <div class="card-body">
                                <h5 class="card-title">${movie.title} (${new Date(movie.release_date).getFullYear()})</h5>
                                <button class="btn btn-success btn-sm">Selecionar</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    }
    </script>
</body>
</html>