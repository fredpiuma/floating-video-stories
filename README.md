# Floating Video Stories for WooCommerce

Plugin WordPress modular que exibe vídeos em uma bolha flutuante, com abertura num player no estilo stories, em produtos WooCommerce e páginas estáticas.

## 🎥 Funcionalidades

- **Vários vídeos por produto ou página**: até 20 MP4, na ordem que você definir arrastando
- **Player no estilo stories**: barras de progresso no topo, avanço automático, setas e toque nas laterais para navegar, segurar para pausar
- **Som**: abre com som quando o navegador permite; senão, toca mudo com botão de alto-falante
- **Bolha Flutuante**: exibe um vídeo em miniatura numa bolha fixa na tela (miniatura própria ou o primeiro vídeo)
- **Migração automática**: produtos e páginas configurados na 1.3.x continuam funcionando sem ação manual
- **Responsivo e acessível**: setas sobre o vídeo no celular, navegação por teclado (←, →, Esc) e foco devolvido à bolha
- **Leve**: CSS e JS só são carregados nas páginas que têm vídeo
- **Extensível**: filtro `fvs_stories` e função `fvs_render_stories_bubble()` para outros tipos de conteúdo

## 📋 Requisitos

- WordPress 5.0+
- PHP 7.2+
- WooCommerce 3.0+ (apenas para funcionalidade de produtos)

## 🚀 Instalação

**[Baixar o plugin (zip da última versão)](https://github.com/fredpiuma/floating-video-stories/releases/latest/download/floating-video-stories.zip)**

Esse zip tem só os arquivos do plugin. Não use o "Source code (zip)" do GitHub para instalar: ele traz arquivos de desenvolvimento e a pasta fica com o nome errado.

### Pelo painel do WordPress

1. Baixe o zip pelo link acima.
2. Em **Plugins → Adicionar novo → Enviar plugin**, escolha o arquivo e clique em **Instalar agora**.
3. Ative o **Fred Video Produtos e Páginas**.

Para atualizar, envie o zip da versão nova da mesma forma e confirme a substituição da versão instalada.

### Pelo WP-CLI

```bash
wp plugin install https://github.com/fredpiuma/floating-video-stories/releases/latest/download/floating-video-stories.zip --activate
```

Para atualizar para a última versão, rode o mesmo comando com `--force`.

Requer WordPress 5.0+ e PHP 7.2+. A parte de produtos requer WooCommerce 3.0+.

## 📖 Como Usar

### Para Produtos WooCommerce

1. Acesse **Produtos** > **Todos os Produtos** e edite um produto
2. Em **Dados do produto**, abra a aba **Vídeos (stories)**
3. Em **Vídeos (stories)**, clique em **Adicionar vídeos** e selecione um ou mais MP4 (outros formatos são recusados com aviso)
4. Arraste pela alça para mudar a ordem; use **Remover** para tirar um vídeo
5. Opcional: em **Miniatura da bolha (MP4, opcional)**, escolha um vídeo curto para a bolha. Se ficar vazio, a bolha usa o primeiro vídeo
6. Atualize o produto

Quando o cliente visita a página do produto, vê a bolha de vídeo no canto esquerdo da tela. Para tirar os vídeos, remova todos da lista e salve.

### Para Páginas

1. Acesse **Páginas** > **Todas as Páginas** e edite uma página
2. Procure pela caixa **Vídeos da Página**
3. Use os mesmos campos do produto: **Miniatura da bolha** e a lista **Vídeos (stories)**
4. Publique a página

O comportamento é idêntico ao dos produtos.

### Vindo da versão 1.3.x

Nada a fazer: o vídeo principal antigo aparece como o primeiro vídeo da lista, e a miniatura antiga continua na bolha. Ao salvar o produto ou a página, os dados passam para o formato novo. Os campos antigos são mantidos no banco, então dá para voltar à 1.3.x sem perder a configuração.

## 🎨 Interface do Usuário

### Bolha de Vídeo
- **Desktop**: Círculo de 90px à esquerda, a 30% da base da tela
- **Mobile**: Círculo de 27% da largura da tela, no canto inferior esquerdo; some temporariamente quando o teclado virtual abre
- **Hover**: Aumenta 8% com efeito suave
- **X na bolha**: Esconde a bolha

### Player stories
- **Barras no topo**: uma por vídeo; a atual enche conforme o vídeo avança
- **Avanço automático**: ao fim de um vídeo, começa o próximo; depois do último, o player fecha
- **Setas** (fora do vídeo no desktop, sobre o vídeo no celular), **toque nas laterais** e **teclas ← / →**: voltar e avançar. "Voltar" no primeiro vídeo o reinicia
- **Segurar** sobre o vídeo: pausa enquanto estiver pressionado
- **Som**: abre com som quando o navegador permite; o botão de alto-falante liga e desliga, e a escolha vale para os vídeos seguintes
- **Fechar**: X, clique fora do vídeo ou tecla Esc. O foco volta para a bolha
- Vídeo que não carrega é pulado; trocar de aba pausa e voltar retoma

## 📁 Estrutura do Projeto

```
floating-video-stories/
├── fred-video-produto.php              # Plugin principal (loader, constantes FVS_*)
├── includes/
│   ├── fred-video-common.php           # Dados (fvs_get_stories), render e assets do front
│   ├── fred-video-admin-field.php      # Campo de stories do admin (render, save, assets)
│   ├── fred-video-produtos.php         # Aba "Vídeos (stories)" do produto WooCommerce
│   └── fred-video-pages.php            # Meta box "Vídeos da Página"
├── assets/
│   ├── css/fvs-front.css               # Bolha e player stories
│   ├── js/fvs-front.js                 # Player stories (JS puro)
│   ├── css/fvs-admin.css               # Estilo do campo no admin
│   └── js/fvs-admin.js                 # wp.media + lista ordenável
├── .github/workflows/release.yml       # Gera e anexa o zip a cada release publicado
└── README.md                           # Este arquivo
```

## 🔧 Desenvolvimento

### Adicionar Suporte para Novo Tipo de Conteúdo

Os vídeos ficam na meta `_fvs_stories` (lista de `['id' => ID do anexo, 'url' => URL do MP4]`, na ordem de exibição) e a miniatura em `_fvs_bubble_thumb`. Leia sempre pelas funções, que já tratam o fallback das metas antigas:

- `fvs_get_stories( $post_id, $context = '' )`: lista de stories (passa pelo filtro `fvs_stories`)
- `fvs_get_bubble_thumb( $post_id )`: URL da miniatura configurada (vazia = usa o primeiro vídeo)
- `fvs_render_stories_bubble( $type, $thumb_url, $stories )`: imprime a bolha e o player e carrega o CSS/JS

Exemplo para posts do blog, reaproveitando o campo do admin:
```php
// Campo no editor de posts
add_action('add_meta_boxes_post', function () {
    add_meta_box('fvs_post_stories', 'Vídeos', function ($post) {
        fvs_render_stories_field($post->ID);
    }, 'post');
});
// Carrega o JS/CSS do campo também no editor de posts
add_filter('fvs_admin_post_types', function ($types) {
    $types[] = 'post';
    return $types;
});
add_action('save_post_post', function ($post_id) {
    $data = fvs_read_stories_request($post_id);
    if (null !== $data) {
        fvs_save_stories_meta($post_id, $data);
    }
});

// Exibição
add_action('wp_footer', function () {
    if (!is_singular('post')) return;
    $stories = fvs_get_stories(get_the_ID(), 'post');
    if ($stories) {
        fvs_render_stories_bubble('post', fvs_get_bubble_thumb(get_the_ID()), $stories);
    }
});
```

Para alterar a lista exibida sem mexer no banco, use o filtro:
```php
add_filter('fvs_stories', function ($stories, $post_id, $context) {
    // ex.: acrescentar um vídeo institucional no fim de todos os produtos
    if ('product' === $context) {
        $stories[] = ['id' => 0, 'url' => 'https://exemplo.com/wp-content/uploads/institucional.mp4'];
    }
    return $stories;
}, 10, 3);
```

A função antiga `fvp_render_video_bubble( $type, $thumb_url, $video_url )` continua disponível e exibe o vídeo como um player de um story só.

### Testar Localmente

```bash
# Verificar sintaxe PHP
php -l fred-video-produto.php
php -l includes/*.php

# Ativar debug no wp-config.php se precisar
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🐛 Troubleshooting

**P: Os vídeos não aparecem nas páginas de produto**
- Confirme que há pelo menos um vídeo na lista **Vídeos (stories)**
- Verifique que os arquivos são MP4 válidos
- Limpe o cache do navegador

**P: O vídeo abre sem som**
- O navegador bloqueou o som automático. Toque no alto-falante no topo do player para ligar
- A bolha sempre toca sem som, como exigem os navegadores

**P: Mais de uma bolha na mesma página causa conflito**
- Não: cada bolha é uma instância independente, sem IDs fixos

## 📝 Notas de Versão

Veja o [CHANGELOG.md](CHANGELOG.md) e os [releases](https://github.com/fredpiuma/floating-video-stories/releases).

## 📄 Licença

Este plugin é fornecido como está. Sinta-se livre para usar, modificar e distribuir.

## 🤝 Contribuições

Contribuições são bem-vindas! Se encontrar bugs ou tiver sugestões, sinta-se livre para:
1. Abrir uma issue
2. Fazer um fork e enviar um pull request
3. Entrar em contato com o autor

**Lançar uma versão:** siga o processo da seção "Versões e releases" do [AGENTS.md](AGENTS.md).

## 📚 Recursos Adicionais

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [WooCommerce Product Hooks](https://woocommerce.com/document/hooks/)
- [HTML5 Video Element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video)

---

### Precisa de uma solução sob medida para a sua loja?

Desenvolvo plugins, integrações e melhorias para WordPress e WooCommerce, do checkout ao pós-venda. Me conte o que você precisa e vamos encontrar o melhor caminho para o seu projeto.

**Frederico de Castro** · Senior Web Developer & Software Architect

[![Fale comigo no WhatsApp](https://img.shields.io/badge/Fale_comigo_no_WhatsApp-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)](https://wa.me/5511914636944)
[![E-mail](https://img.shields.io/badge/E--mail-D14836?style=for-the-badge&logo=gmail&logoColor=white)](mailto:contato@fredericodecastro.com.br)
[![Site](https://img.shields.io/badge/Site-1F2937?style=for-the-badge&logo=googlechrome&logoColor=white)](https://www.fredericodecastro.com.br/links/)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-0A66C2?style=for-the-badge)](https://www.linkedin.com/in/fredericodecastro/)
[![Telegram](https://img.shields.io/badge/Telegram-2CA5E0?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/fredericomdecastro)
