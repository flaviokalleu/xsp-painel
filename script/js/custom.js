document.addEventListener("DOMContentLoaded", function() {
    function addOverflowAuto() {
        var wrapper = document.getElementById("data_table_wrapper");
        if (wrapper) {
            var layoutCell = wrapper.querySelector(".dt-layout-table");
            if (layoutCell) {
                layoutCell.classList.add("overflow-auto");
            }
        }
    }
    var observer = new MutationObserver(function() { addOverflowAuto(); });
    observer.observe(document.body, { childList: true, subtree: true });
    addOverflowAuto();
});

async function updateinfo() {
    try {
        const response = await fetch(`api/listar-clientes.php?info_admin`);
        if (!response.ok) { throw new Error('Erro ao carregar dados'); }
        const jsonResponse = await response.json();
        if (jsonResponse.icon === 'error') {
            SweetAlert2(jsonResponse.title, jsonResponse.msg, jsonResponse.icon);
        } else {
            // LINHA ATUALIZADA AQUI para incluir a versão mobile dos créditos
            $('#creditos, #creditos2, #creditos-mobile').html(jsonResponse.creditos);
            $('#tipo_admin').html(jsonResponse.tipo_admin);
        }
    } catch (error) {
        console.error('Falha ao atualizar informações do admin:', error);
    }
}

updateinfo();

async function modal_master(url, parametro1, valor1, parametro2, valor2) {
    var postData = { [parametro1]: valor1 };
    if (parametro2 !== undefined && valor2 !== undefined) postData[parametro2] = valor2;
    $.ajax({
        type: "POST",
        url: url,
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response && response.modal_titulo) {
                $('#modal_master-titulo').html(response.modal_titulo);
                $('#modal_master-body').html(response.modal_body);
                $('#modal_master-footer').html(response.modal_footer);
                $('#modal_master').modal('show');
            } else if (response && response.icon === 'error') {
                SweetAlert2(response.title, response.msg, response.icon);
            }
        },
        error: function() {
            SweetAlert2('Erro!', 'Erro de comunicação ao carregar o modal.', 'error');
        }
    });
}

var solicitacaoPendente = false;
function enviardados(id_formulario, url) {
    if (solicitacaoPendente) {
        SweetAlert3('Aguarde!', 'Solicitação em andamento...', 'warning');
        return;
    }
    solicitacaoPendente = true;

    var dados = $("#" + id_formulario).serialize();
    var formData = new FormData(document.getElementById(id_formulario));
    
    $.ajax({
        type: "POST",
        url: "api/" + url,
        data: dados,
        dataType: 'json',
        success: function(response) {
            if (!response) {
                SweetAlert2('Erro!', 'Resposta do servidor vazia.', 'error');
                return;
            }

            SweetAlert2(response.title, response.msg, response.icon);

            if (response.icon === 'success') {
                $("#modal_master").modal('hide');
                if ($.fn.DataTable.isDataTable('#data_table')) {
                    $('#data_table').DataTable().ajax.reload(null, false);
                }
                updateinfo();

                // BLOCO PARA CRIAR NOVO REVENDEDOR
                if (formData.has('confirme_add_revendedor') && response.data && response.data.usuario) {
                    const userData = response.data;
                    const messageForWhatsapp = `*Seja bem-vindo(a) à X BLACK!* 🚀\n\n` + `Seguem seus dados de acesso ao painel de revenda:\n\n` + `🔗 *Link do Painel:* ${userData.link_painel}\n` + `👤 *Usuário:* ${userData.usuario}\n` + `🔑 *Senha:* ${userData.senha}\n\n` + `Atenciosamente,\n` + `*Equipe X BLACK*`;
                    const messageForDisplay = `<strong>Seja bem-vindo(a) à X BLACK!</strong> 🚀<br><br>` + `Seguem seus dados de acesso ao painel de revenda:<br><br>` + `🔗 <strong>Link do Painel:</strong> <a href="${userData.link_painel}" target="_blank">${userData.link_painel}</a><br>` + `👤 <strong>Usuário:</strong> ${userData.usuario}<br>` + `🔑 <strong>Senha:</strong> ${userData.senha}<br><br>` + `Atenciosamente,<br>` + `<strong>Equipe X BLACK</strong>`;
                    
                    const infoContainer = document.getElementById('dadosRevendedorParaCopiar');
                    const infoModalEl = document.getElementById('infoRevendedorModal');
                    if (infoContainer && infoModalEl) {
                         infoContainer.innerHTML = messageForDisplay;
                         infoContainer.setAttribute('data-copy-text', messageForWhatsapp);
                         const infoModal = new bootstrap.Modal(infoModalEl);
                         infoModal.show();
                    }
                }

                // BLOCO PARA CONFIRMAÇÃO DE CRÉDITOS
                if (formData.has('confirme_add_creditos') && response.data && response.data.usuario) {
                    const creditData = response.data;
                    const creditosAbs = Math.abs(creditData.creditos_adicionados);
                    const acaoTexto = creditData.creditos_adicionados >= 0 ? 'adicionado' : 'removido';
                    const acaoEmoji = creditData.creditos_adicionados >= 0 ? '✅' : '↪️';
                    const creditosPalavra = creditosAbs === 1 ? 'crédito' : 'créditos';

                    const messageForWhatsapp = `${acaoEmoji} *Operação de Créditos Concluída!*\n\n` +
                                                 `Olá, ${creditData.usuario}!\n` +
                                                 `Foi ${acaoTexto} *${creditosAbs} ${creditosPalavra}* em sua conta com sucesso.`;

                    const messageForDisplay = `<p class="fw-bold">${acaoEmoji} Operação de Créditos Concluída!</p>` +
                                                  `<p>Olá, <strong>${creditData.usuario}</strong>!</p>` +
                                                  `<p>Foi ${acaoTexto} <strong>${creditosAbs} ${creditosPalavra}</strong> em sua conta com sucesso.</p>`;
                    
                    const creditContainer = document.getElementById('dadosCreditosParaCopiar');
                    const creditModalEl = document.getElementById('infoCreditosModal');

                    if (creditContainer && creditModalEl) {
                        creditContainer.innerHTML = messageForDisplay;
                        creditContainer.setAttribute('data-copy-text', messageForWhatsapp);
                        const creditModal = new bootstrap.Modal(creditModalEl);
                        creditModal.show();
                    }
                }
            }
        },
        error: function() {
            SweetAlert2('Erro!', 'Erro na solicitação ao servidor.', 'error');
        },
        complete: function() {
            solicitacaoPendente = false;
        }
    });
}

function SweetAlert2(title, text, icon) {
    Swal.fire({ title: title, html: text, icon: icon });
}

function SweetAlert3(title, icon, timer) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: timer || 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
    Toast.fire({ icon: icon, title: title });
}