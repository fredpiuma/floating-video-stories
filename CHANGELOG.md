# Changelog

## [1.5.0] - 2026-11-25

- Nova página **Configurações › Floating Video Stories** (também acessível pelo link "Configurações" na lista de plugins), para escolher **separadamente no computador e no celular**:
  - o **lado** da bolha (esquerda ou direita) e a distância da lateral;
  - se a distância vertical é medida a partir do **topo** ou da **base** da tela;
  - o **tamanho** da bolha, em px ou em % da largura da tela.
- Seção **Avançado**: a **largura de tela** a partir da qual vale a configuração de celular (padrão 640px; use 1024px para incluir tablets) e a **camada (z-index)**, para resolver sobreposição com botões de WhatsApp, carrinho ou chat. O player de vídeo continua sempre acima da bolha.
- **Prévia ao vivo** da posição e do tamanho em cada dispositivo, e botão **Restaurar padrão** por dispositivo.
- Com a bolha à direita, o X de fechar fica no canto direito e a bolha sai pela direita quando o teclado do celular abre.
- O X da bolha agora acompanha o tamanho dela e nunca fica menor que 24px, para continuar fácil de tocar.
- **Nada muda para quem não alterar as configurações:** os valores padrão reproduzem a posição e o tamanho das versões anteriores.
- Ao desinstalar o plugin, as configurações são apagadas; os vídeos dos produtos e páginas são mantidos.
- Para desenvolvedores: filtro `fvs_bubble_settings` para mudar posição e tamanho por produto ou página via código.

## [1.4.2] - 2026-11-24

- README: o comando de instalação pelo WP-CLI agora já inclui `--force`, então o mesmo comando instala e atualiza o plugin.
- Nenhuma mudança no funcionamento do plugin.

## [1.4.1] - 2026-11-24

- O plugin passou a aparecer na lista de plugins do WordPress como **Floating Video Stories for WooCommerce**, o nome oficial do projeto. Ele continua ativo após a atualização, sem precisar reativar.
- Link do autor atualizado.
- Nenhuma mudança no funcionamento do plugin.

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
