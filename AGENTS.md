# Floating Video Stories for WooCommerce

Plugin WordPress que exibe vídeos numa bolha flutuante (com lightbox) em produtos WooCommerce e páginas.
Arquivo principal: `fred-video-produto.php`; módulos em `includes/`. A pasta do plugin (slug) é `floating-video-stories`.

## Versões e releases

Toda melhoria ou correção entregue vira uma versão nova publicada no GitHub. Não termine a tarefa sem o release.

1. Escolha a versão (SemVer): correção → `X.Y.Z+1`; recurso novo ou melhoria → `X.Y+1.0`; quebra de compatibilidade → `X+1.0.0`.
2. Atualize a versão em todos estes lugares: cabeçalho `Version:` de `fred-video-produto.php` (é o único lugar hoje; se criar constante de versão, `readme.txt` etc., acrescente-os aqui e no workflow).
3. Acrescente a entrada `## [X.Y.Z] - AAAA-MM-DD` no topo do `CHANGELOG.md`, em português, dizendo o que mudou para quem usa o plugin.
4. Confira `php -l` nos arquivos PHP alterados.
5. Commit, push do `master`, e crie o release com as notas tiradas do CHANGELOG:
   `gh release create vX.Y.Z --title "Floating Video Stories X.Y.Z" --notes-file <notas.md> --latest`
   (não anexe arquivos: o workflow `.github/workflows/release.yml` gera e anexa `floating-video-stories.zip` e `floating-video-stories-X.Y.Z.zip`).
6. Acompanhe o workflow: `gh run list --workflow release.yml -L 1` e `gh run watch <id> --exit-status`.
7. Confirme: `gh release view vX.Y.Z --json assets -q '.assets[].name'` lista os dois zips, e
   `curl -sIL -o /dev/null -w "%{http_code}" https://github.com/fredpiuma/floating-video-stories/releases/latest/download/floating-video-stories.zip` responde `200`.
8. Se o workflow falhar por erro no próprio workflow: corrija no `master` e recrie release e tag
   (`gh release delete vX.Y.Z --yes --cleanup-tag` + `gh release create`), porque o workflow usa o código da tag.
   Liste o zip com `unzip -Z1`, nunca `unzip -l`.

Não renomeie `fred-video-produto.php`: isso desativaria o plugin nos sites onde ele já está instalado.
