jQuery(document).ready(function($){
    function carregarItens(form, append=false, callback){
        var data = form.serializeArray().reduce((obj,item)=>{
            obj[item.name]=item.value; return obj;
        },{});
        data['action']='bf_filtrar';
        // nonce seja enviado (caso não esteja no form)
        if(typeof bf_ajax_obj.nonce !== "undefined" && !data['bf_filtrar_nonce']){
            data['bf_filtrar_nonce'] = bf_ajax_obj.nonce;
        }
        $.post(bf_ajax_obj.ajax_url, data, function(response){
            if(response.success === false && response.data && response.data.message){
                alert(response.data.message);
                return;
            }
            if(typeof response === "object" && response.hasOwnProperty('data')) {
                // Caso wp_send_json_error seja usado
                return;
            }
            if(typeof response === "string" && response.trim() === "") return;
            if(append){
                $('.bf-grid').append(response);
            } else {
                $('.bf-grid').html(response);
            }
            if(typeof callback === "function") callback();
        });
    }

    var form = $('.bf-filtros');
    var carregando = false;

    // Primeiro carregamento
    carregarItens(form);

    // Filtrar
    form.on('submit', function(e){
        e.preventDefault();
        form.find('input[name="pagina_atual"]').val(1);
        carregarItens(form);
    });

    // Scroll infinito
    $(window).on('scroll', function(){
        if(carregando) return;
        if($(window).scrollTop() + $(window).height() > $(document).height() - 300){
            carregando = true;
            var paginaAtual = parseInt(form.find('input[name="pagina_atual"]').val()) + 1;
            form.find('input[name="pagina_atual"]').val(paginaAtual);
            carregarItens(form, true, function(){
                carregando = false;
            });
        }
    });
});