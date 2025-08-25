<?php
/**
 * Plugin Name: Biblioteca Filtrável
 * Description: Galeria de imagens e vídeos com filtros (idade, cor, altura, gênero, nacionalidade, qualidade, formato, local, ação). Miniaturas automáticas de links externos e scroll infinito.
 * Version: 1.5
 * Author: Levi
 */

if(!defined('ABSPATH')) exit;

// ---------- CPT ----------
function bf_register_cpt_biblioteca() {
    $labels = [
        'name'=>'Biblioteca',
        'singular_name'=>'Item da Biblioteca',
        'add_new'=>'Adicionar Item',
        'add_new_item'=>'Adicionar Novo Item',
        'edit_item'=>'Editar Item',
        'all_items'=>'Todos os Itens',
    ];

    $args = [
        'labels'=>$labels,
        'public'=>true,
        'has_archive'=>true,
        'show_in_rest'=>true,
        'rest_base'=>'biblioteca',
        'supports'=>['title','thumbnail'],
        'menu_icon'=>'dashicons-images-alt2',
    ];

    register_post_type('biblioteca',$args);
}
add_action('init','bf_register_cpt_biblioteca');

// ---------- Campos personalizados ----------
function bf_register_meta_fields() {
    $fields = [
        'url_da_midia'=>'string',
        'fonte'=>'string',
        'tipo'=>'string',
        'idade'=>'number',
        'cor_pele'=>'string',
        'cor_cabelo'=>'string',
        'altura'=>'number',
        'genero'=>'string',
        'tags'=>'string',
        'nacionalidade'=>'string',
        'qualidade'=>'string', // Profissional / Caseiro
        'formato'=>'string',   // Vertical / Horizontal
        'local'=>'string',     // Casa, Cozinha, Carro, Rua, Ar Livre
        'acao'=>'string',      // Sentada, Caminhando, Cozinhando
    ];

    foreach($fields as $field=>$type){
        register_post_meta('biblioteca',$field,[
            'show_in_rest'=>true,
            'single'=>true,
            'type'=>$type,
        ]);
    }
}
add_action('init','bf_register_meta_fields');

// ---------- Enfileirar scripts ----------
function bf_enqueue_scripts(){
    wp_enqueue_script('bf-ajax',plugin_dir_url(__FILE__).'bf-ajax.js',['jquery'],null,true);
    wp_localize_script('bf-ajax','bf_ajax_obj',[
        'ajax_url'=>admin_url('admin-ajax.php'),
        'nonce'=>wp_create_nonce('bf_filtrar_nonce')
    ]);
}
add_action('wp_enqueue_scripts','bf_enqueue_scripts');

// ---------- Shortcode ----------
function bf_shortcode_galeria($atts){
    $atts = shortcode_atts([
        'per_page'=>24,
        'columns'=>6,
    ],$atts,'biblioteca');

    ob_start();
    ?>
    <form method="post" class="bf-filtros">
        <input type="hidden" name="pagina_atual" value="1" />
        <input type="hidden" name="per_page" value="<?php echo intval($atts['per_page']); ?>" />
        <input type="hidden" name="bf_filtrar_nonce" value="<?php echo esc_attr(wp_create_nonce('bf_filtrar_nonce')); ?>" />
        Idade: <input type="number" name="idade_min" placeholder="Min" /> -
        <input type="number" name="idade_max" placeholder="Max" />
        Altura: <input type="number" name="altura_min" placeholder="Min" /> -
        <input type="number" name="altura_max" placeholder="Max" />
        Cor da pele:
        <select name="cor_pele">
            <option value="">Todos</option>
            <option value="clara">Clara</option>
            <option value="media">Média</option>
            <option value="escura">Escura</option>
        </select>
        Cor do cabelo:
        <select name="cor_cabelo">
            <option value="">Todos</option>
            <option value="loiro">Loiro</option>
            <option value="castanho">Castanho</option>
            <option value="preto">Preto</option>
            <option value="ruivo">Ruivo</option>
        </select>
        Gênero:
        <select name="genero">
            <option value="">Todos</option>
            <option value="homem">Homem</option>
            <option value="mulher">Mulher</option>
        </select>
        Nacionalidade:
        <select name="nacionalidade">
            <option value="">Todos</option>
            <option value="brasil">Brasil</option>
            <option value="estados_unidos">Estados Unidos</option>
            <option value="china">China</option>
            <option value="india">Índia</option>
            <option value="russia">Rússia</option>
            <option value="mexico">México</option>
            <option value="alemanha">Alemanha</option>
            <option value="franca">França</option>
            <option value="japao">Japão</option>
            <option value="canada">Canadá</option>
            <option value="australia">Austrália</option>
            <option value="reino_unido">Reino Unido</option>
            <option value="espanha">Espanha</option>
            <option value="italia">Itália</option>
            <option value="nigeria">Nigéria</option>
        </select>
        Qualidade:
        <select name="qualidade">
            <option value="">Todos</option>
            <option value="profissional">Profissional</option>
            <option value="caseiro">Caseiro</option>
        </select>
        Formato:
        <select name="formato">
            <option value="">Todos</option>
            <option value="vertical">Vertical</option>
            <option value="horizontal">Horizontal</option>
        </select>
        Local:
        <select name="local">
            <option value="">Todos</option>
            <option value="casa">Casa</option>
            <option value="cozinha">Cozinha</option>
            <option value="carro">Carro</option>
            <option value="rua">Rua</option>
            <option value="ar_livre">Ar Livre</option>
        </select>
        Ação:
        <select name="acao">
            <option value="">Todos</option>
            <option value="sentada">Sentada</option>
            <option value="caminhando">Caminhando</option>
            <option value="cozinhando">Cozinhando</option>
        </select>
        Fonte:
        <input type="text" name="fonte" placeholder="Fonte" />
        Tipo:
        <input type="text" name="tipo" placeholder="Tipo" />
        Tags:
        <input type="text" name="tags" placeholder="Tags separadas por vírgula" />
    </form>
    <div class="bf-grid cols-<?php echo intval($atts['columns']); ?>"></div>
    <style>
    .bf-grid{display:grid;gap:12px;margin-top:12px;}
    .bf-grid.cols-6{grid-template-columns:repeat(6,1fr);}
    .bf-card img{width:100%;height:150px;object-fit:cover;border-radius:6px;}
    .bf-filtros{margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;}
    .bf-filtros input,.bf-filtros select{padding:4px 6px;}
    @media(max-width:768px){.bf-grid.cols-6{grid-template-columns:repeat(2,1fr);}}
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('biblioteca','bf_shortcode_galeria');

// ---------- AJAX Handler ----------
function bf_ajax_filtrar_galeria(){
    // Segurança: Verifica nonce
    if (empty($_POST['bf_filtrar_nonce']) || !wp_verify_nonce($_POST['bf_filtrar_nonce'], 'bf_filtrar_nonce')) {
        wp_send_json_error(['message'=>'Falha de segurança.']);
        wp_die();
    }

    $meta_query = ['relation'=>'AND'];
    if(!empty($_POST['idade_min'])) $meta_query[] = ['key'=>'idade','value'=>intval($_POST['idade_min']),'compare'=>'>=','type'=>'NUMERIC'];
    if(!empty($_POST['idade_max'])) $meta_query[] = ['key'=>'idade','value'=>intval($_POST['idade_max']),'compare'=>'<=','type'=>'NUMERIC'];
    if(!empty($_POST['altura_min'])) $meta_query[] = ['key'=>'altura','value'=>intval($_POST['altura_min']),'compare'=>'>=','type'=>'NUMERIC'];
    if(!empty($_POST['altura_max'])) $meta_query[] = ['key'=>'altura','value'=>intval($_POST['altura_max']),'compare'=>'<=','type'=>'NUMERIC'];
    if(!empty($_POST['cor_pele'])) $meta_query[] = ['key'=>'cor_pele','value'=>sanitize_text_field($_POST['cor_pele']),'compare'=>'='];
    if(!empty($_POST['cor_cabelo'])) $meta_query[] = ['key'=>'cor_cabelo','value'=>sanitize_text_field($_POST['cor_cabelo']),'compare'=>'='];
    if(!empty($_POST['genero'])) $meta_query[] = ['key'=>'genero','value'=>sanitize_text_field($_POST['genero']),'compare'=>'='];
    if(!empty($_POST['nacionalidade'])) $meta_query[] = ['key'=>'nacionalidade','value'=>sanitize_text_field($_POST['nacionalidade']),'compare'=>'='];
    if(!empty($_POST['qualidade'])) $meta_query[] = ['key'=>'qualidade','value'=>sanitize_text_field($_POST['qualidade']),'compare'=>'='];
    if(!empty($_POST['formato'])) $meta_query[] = ['key'=>'formato','value'=>sanitize_text_field($_POST['formato']),'compare'=>'='];
    if(!empty($_POST['local'])) $meta_query[] = ['key'=>'local','value'=>sanitize_text_field($_POST['local']),'compare'=>'='];
    if(!empty($_POST['acao'])) $meta_query[] = ['key'=>'acao','value'=>sanitize_text_field($_POST['acao']),'compare'=>'='];
    if(!empty($_POST['fonte'])) $meta_query[] = ['key'=>'fonte','value'=>sanitize_text_field($_POST['fonte']),'compare'=>'LIKE'];
    if(!empty($_POST['tipo'])) $meta_query[] = ['key'=>'tipo','value'=>sanitize_text_field($_POST['tipo']),'compare'=>'LIKE'];
    if(!empty($_POST['tags'])) $meta_query[] = ['key'=>'tags','value'=>sanitize_text_field($_POST['tags']),'compare'=>'LIKE'];

    $pagina = !empty($_POST['pagina_atual']) ? intval($_POST['pagina_atual']) : 1;
    $per_page = !empty($_POST['per_page']) ? intval($_POST['per_page']) : 24;

    $query = new WP_Query([
        'post_type'=>'biblioteca',
        'posts_per_page'=>$per_page,
        'paged'=>$pagina,
        'meta_query'=>$meta_query,
    ]);

    if($query->have_posts()){
        while($query->have_posts()): $query->the_post();
            $url = get_post_meta(get_the_ID(),'url_da_midia',true);
            $title = get_the_title();
            if(!$url) continue;

            $thumb = get_the_post_thumbnail_url(get_the_ID(),'medium');
            if(!$thumb){
                $thumb = $url;
            }

            echo '<a class="bf-card" href="'.esc_url($url).'" target="_blank" rel="noopener nofollow">';
            echo '<img src="'.esc_url($thumb).'" alt="'.esc_attr($title).'" loading="lazy" />';
            echo '</a>';
        endwhile;
    } else {
        if($pagina==1) echo '<p>Nenhum item encontrado.</p>';
    }
    wp_reset_postdata();
    wp_die();
}
add_action('wp_ajax_bf_filtrar','bf_ajax_filtrar_galeria');
add_action('wp_ajax_nopriv_bf_filtrar','bf_ajax_filtrar_galeria');