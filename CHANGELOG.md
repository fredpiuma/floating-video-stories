# Changelog

## [1.4.0] - 2026-11-24

- Produtos e páginas agora aceitam **vários vídeos** (até 20), na ordem que você definir arrastando na lista.
- Ao clicar na bolha, os vídeos abrem num **player no estilo stories**: barras no topo mostram o progresso de cada vídeo e o próximo começa sozinho quando o atual termina. Depois do último, o player fecha.
- **Setas** à esquerda e à direita do vídeo (sobre o vídeo no celular), toque nas laterais e teclas ← / → para voltar e avançar. Segurar o dedo ou o mouse sobre o vídeo pausa.
- O vídeo abre **com som** quando o navegador permite; se ele bloquear, toca sem som e o botão de alto-falante no topo liga o som.
- No produto, os vídeos ficam na nova aba **"Vídeos (stories)"** dos Dados do produto. Na página, na caixa **"Vídeos da Página"**.
- A miniatura da bolha virou opcional: sem ela, a bolha mostra o primeiro vídeo.
- **Migração automática:** produtos e páginas configurados nas versões anteriores continuam funcionando sem nenhuma ação; o vídeo principal antigo aparece como o primeiro vídeo da lista. Os dados antigos são preservados, então dá para voltar à 1.3.x se precisar.
- Mais segurança ao salvar os vídeos da página (verificação de permissão e de origem do formulário).
- CSS e JavaScript agora são arquivos separados, carregados só nas páginas que têm vídeo.

## [1.3.2] - 2026-11-24

- O zip de instalação passou a ser gerado só no GitHub, a cada release. Nenhuma mudança no funcionamento do plugin.

## [1.3.1] - 2026-11-24

- Instruções de instalação no README (zip do último release e WP-CLI).
- Cada release agora traz o zip de instalação `floating-video-stories.zip`, pronto para enviar pelo painel do WordPress.
- Nenhuma mudança no funcionamento do plugin.

## [1.3.0] - 2026-04-01

- A bolha de vídeo passou a ficar a 30% da base da tela.
- No celular, o vídeo é ocultado temporariamente quando o teclado abre.

## [1.2.0]

- Vídeos também em páginas estáticas, além dos produtos WooCommerce.
- Código separado em módulos (produtos, páginas e funções comuns).
