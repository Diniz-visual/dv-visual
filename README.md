# DV Visual — Tema WordPress

Tema WordPress híbrido/clássico completo para WordPress 6.5 ou superior, criado para o portfólio da DV Visual.

A versão 4.26.0 remodela a página individual do Blog com abertura editorial, breadcrumb, categoria, título, resumo, autor, data, tempo de leitura, imagem destacada ampla, compartilhamento, resposta rápida, sumário automático, categorias laterais, navegação entre artigos, comentários e CTA. Os breadcrumbs agora atendem Blog, páginas, arquivos, buscas, categorias, Custom Post Types e 404, com controles globais em **DV Visual → Breadcrumbs** para visibilidade, rótulos, separador, página atual, estilo e cores. A versão preserva o Scroll container de imagens/PDFs dos cases, a sidebar administrativa reativa, a tipografia Manrope, os filtros, o carregamento AJAX, os carrosséis Swiper, o Bootstrap Offcanvas, o SEO de fallback e as logos transparentes.

## Instalação

1. No WordPress, acesse **Aparência → Temas → Adicionar tema → Enviar tema**.
2. Selecione `dv-visual.zip`.
3. Instale e ative.
4. Edite o conteúdo em **Páginas** e atribua o modelo desejado na área **Modelo** do editor.
5. Instale e ative o **ACF PRO**. Os campos das seções e dos slides são carregados automaticamente pelo tema.
6. Em **Aparência → Menus → Gerenciar posições**, atribua os menus a **Menu principal DV**, **Menu do rodapé DV** e **Redes sociais DV**.
7. Defina o logotipo em **Aparência → Personalizar → Identidade do site**.

Na primeira ativação, o tema cria e atribui menus padrão totalmente editáveis, as 13 seções iniciais da Home e três slides demonstrativos. Conteúdo já existente nunca é sobrescrito.

## Incluído

- Templates PHP clássicos com `theme.json`, padrões de blocos e a paleta oficial DV Visual
- `front-page.php` e `page-home.php` para a Home, montados por `template-parts/pages/home.php`
- Seções independentes da Home em `template-parts/home/`, fáceis de reordenar, remover ou programar separadamente
- Custom Post Type **Home — Seções** com visibilidade, tipo, ordem, âncora, classes CSS e editor de blocos
- Custom Post Type **Home — Slides** com textos, CTAs, banners e artes separadas para desktop/mobile, cores, métricas e duração
- **Home — Construtor** com cards arrasta-e-solta para reorganizar todas as seções
- Coluna **Blocos ativos**: tudo o que estiver nela aparece na Home
- Coluna **Todos os blocos**: seções inativas permanecem salvas, mas não aparecem no site
- Arrastar entre as colunas ativa ou desativa automaticamente, sem excluir conteúdo
- Biblioteca visual para adicionar seções prontas ou seções personalizadas
- Botões de mostrar/ocultar, mover por teclado e abrir os blocos internos
- Prévia ao vivo da Home nos modos desktop, tablet e celular
- Salvamento automático da ordem e atualização imediata da prévia
- Migração automática e não destrutiva dos slides configurados nas versões anteriores
- Comentários PHP em todos os `template-parts` da Home e comentários HTML de início/fim no código-fonte público
- `page.php`, `home.php`, `index.php`, `archive.php`, `single.php`, `search.php`, `404.php`, `header.php`, `footer.php` e `comments.php`
- Modelos de página para Home, Estúdio, Portfólio, Produtos, Soluções, Ajuda, Contato, Landing Page e página em branco
- Home completa com hero em carrossel, confiança, soluções, manifesto, portfólio, métricas, processo, depoimentos, FAQ, blog e CTA
- Página de Portfólio editorial com hero gerenciável, métricas, filtros instantâneos por Segmentos, contagem dinâmica, grade de cases e CTA final
- Cards de projetos padronizados na Home e no Portfólio, com imagem destacada, segmento, escopo, hover em vidro e acesso ao case individual
- Página individual de projeto com abertura em imagem destacada, ficha de cliente/setor/serviço/ano, editor visual do WordPress, blocos ACF de desafio/estratégia/solução, resultados, paleta, vídeo e galeria responsiva
- Lightbox da galeria com imagem ampliada, contador, legenda, anterior/próxima, gestos de toque, setas do teclado, tecla Esc e controle de foco
- Projetos relacionados priorizados pelo mesmo segmento e botão **Ver portfólio** exibido somente no encerramento do case
- Cabeçalho responsivo com Bootstrap Offcanvas, empilhamento corrigido para WordPress/Elementor e animação escalonada
- Mesmo cabeçalho de vidro, logotipo, menu, CTA e rodapé global em todos os templates
- Rodapé editorial com cinco colunas, contatos, redes sociais e seis áreas de widgets nativas
- Ícones sociais nativos para Instagram, LinkedIn, Facebook, WhatsApp, YouTube e X, vinculados à seleção do painel
- Proteção global contra rolagem horizontal, textos e mídias excedendo a tela
- Pontos de quebra dedicados para 280–380px, celulares, tablets, notebooks e telas grandes
- Fallbacks de viewport e eventos responsivos para navegadores móveis antigos
- Superfícies de vidro, microinterações e reveal ao rolar
- Swiper.js padronizado no hero, marcas, serviços mobile e depoimentos, todos em loop contínuo com dois ou mais itens, toque, teclado, setas, paginação, autoplay pausável e respeito à preferência de movimento reduzido
- Área segura nos carrosséis para hover, sombra e foco sem recortes
- Grade automática com os quatro serviços mais recentes, CTA condicional e Swiper no mobile
- Campo ACF **Ícone da solução** abaixo da imagem destacada, integrado aos cards e listas de Soluções
- Cards amplos de vidro na página e no arquivo de Soluções, com duas colunas no desktop e uma no celular
- Página individual do blog com hero editorial, tempo de leitura, autor, imagem destacada e conteúdo tipográfico
- Compartilhamento para LinkedIn, Facebook e WhatsApp, cópia do link e caixa de resposta rápida na abertura do artigo
- Breadcrumb global com dados estruturados, hierarquia automática e painel próprio em **DV Visual → Breadcrumbs**
- URLs dos posts padrão em `/blog/nome-do-post/`, com atualização automática das regras do WordPress
- Lateral responsiva em vidro com categorias, contagem de artigos e destaque da categoria atual
- Sumário automático criado a partir dos títulos H2/H3, com rolagem suave e indicação da seção atual
- Todas as marcas publicadas em Clientes aparecem automaticamente no carrossel de confiança
- Hero gerenciável em **Home — Slides**, com textos, CTAs, métricas, variações de cor e duração por slide
- Banner desktop e banner mobile em tela cheia, mais artes opcionais separadas para cada dispositivo, com enquadramento independente, intensidade do overlay e cor dos textos
- Clientes, soluções, depoimentos, produtos, ferramentas e guias alimentam automaticamente as seções da home
- Animações acessíveis com suporte a movimento reduzido
- Templates exclusivos de listagem e página individual para todos os 11 tipos de conteúdo
- Dez blocos dinâmicos nativos que continuam disponíveis no editor de blocos
- Três posições de menu do WordPress, com menus iniciais editáveis e fallback automático
- Logotipo nativo do WordPress integrado ao cabeçalho e sincronizado com o ícone do site
- Menu mobile Bootstrap Offcanvas com foco, teclado, submenus expansíveis e fechamento automático
- Busca e filtros por taxonomia em cada arquivo, cards responsivos, paginação, galerias, destaques, métricas e conteúdos relacionados
- Sumário automático para posts, guias e artigos de ajuda, além de ficha completa para cases de portfólio
- Arquivos PHP próprios para início, estúdio, serviços, produtos, contato, ajuda, landing pages, arquivos, posts, páginas, projetos, busca e 404
- Blog completo com categorias, tags, autores, comentários, navegação entre artigos e paginação
- WooCommerce preparado
- Tailwind CSS 4 preparado para desenvolvimento
- 11 tipos de conteúdo: Portfólio, Produtos, Serviços, Ferramentas, Guias, Ajuda, Vagas, Depoimentos, Equipe, Clientes e Prêmios
- Taxonomias para categorias de solução, segmentos, especialidades, conteúdo e áreas de vagas
- Dez grupos ACF JSON, incluindo gerenciador de seções, slides do Hero, ícones de Soluções, ficha completa de projeto e 12 layouts flexíveis
- Campos para SEO, compartilhamento, integrações, dados globais, galerias, resultados, paletas e vídeos

## Advanced Custom Fields

Instale e ative o **ACF PRO**. O tema carrega automaticamente os campos e cria a área **DV Visual** no painel, com configurações de marca, cabeçalho, hero, carrosséis, rodapé, contato e integrações. Não é necessário sincronizar manualmente o grupo do hero.

Em **DV Visual → Marca**, envie a **Logo principal** e a **Logo para fundo escuro**. O cabeçalho, o menu mobile e o rodapé priorizam esses arquivos e usam o logo nativo do WordPress apenas como alternativa. Os campos aceitam SVG sanitizado, PNG e WebP; use sempre arte com fundo transparente.

## Onde editar a Home

### Pelo painel do WordPress

- **Home — Construtor → Blocos ativos:** arraste e ordene tudo o que deve aparecer na Home
- **Home — Construtor → Todos os blocos:** arraste para cá tudo o que deve ficar invisível, mas guardado para uso futuro
- **Home — Construtor → Todas as seções:** acesse a listagem tradicional
- **Editar blocos:** dentro de cada card, abre o editor nativo para arrastar textos, imagens, colunas, botões e componentes internos
- **Home — Construtor → Home — Slides:** edite o Hero completo; o título do post é o título grande do slide
- **DV Visual → Hero e Carrosséis:** altere somente autoplay/velocidade geral do Hero e do carrossel de marcas; os “slides antigos” são mantidos apenas como fallback
- **Soluções:** alimenta automaticamente as grades/carrosséis; em cada item, use **Ícone da solução**, abaixo da imagem destacada, para escolher o ícone do card
- **Clientes:** alimenta automaticamente “Marcas que confiam”
- **Portfólio:** alimenta automaticamente os projetos em destaque
- **Depoimentos:** alimenta automaticamente o carrossel de relatos
- **Produtos**, **Ferramentas**, **Guias** e **Posts:** alimentam suas respectivas seções
- **Aparência → Menus:** configure os menus principal, rodapé e redes sociais
- **Aparência → Personalizar → Identidade do site:** logotipo alternativo caso os campos de Marca estejam vazios

### Pelo código

- Entrada da página: `front-page.php` e modelo alternativo `page-home.php`
- Montagem e ordem dinâmica: `template-parts/pages/home.php`
- Gerenciador, criação inicial e leitura dos campos: `inc/home-manager.php`
- Interface arrasta-e-solta: `assets/js/home-builder.js` e `assets/css/home-builder.css`
- Hero/Swiper e componentes dinâmicos: `inc/render.php`
- Uma parte por arquivo: `template-parts/home/hero.php`, `client-trust.php`, `problems.php`, `solution-ecosystem.php`, `portfolio-showcase.php`, `testimonials.php` e os demais arquivos da pasta
- Seção livre: crie um item em **Home — Seções**, selecione “Seção personalizada” e monte seus blocos

Todos os arquivos em `template-parts/home/` possuem comentários indicando exatamente onde o conteúdo correspondente é alterado. No HTML público, procure pelos comentários `DV HOME: INÍCIO` e `DV HOME: FIM` para identificar rapidamente cada faixa.

## Desenvolvimento com Tailwind

Execute `npm install` e depois `npm run watch:css` dentro da pasta do tema.

## v4.27.0 — Conteúdo e SEO editáveis
- Todas as páginas passam a exibir o painel **DV Visual — Textos e títulos da página**, com kicker, texto de apoio e até 6 conjuntos de título/sobretítulo/texto para seções fixas do template.
- **DV Visual → Textos do site** centraliza títulos globais do Processo, FAQ, CTA, Blog, Portfólio da Home, Rodapé e página 404.
- O grupo existente **DV Visual — Hero e SEO** passa a ficar disponível em todos os tipos de conteúdo públicos.
- Sem Yoast, Rank Math, AIOSEO ou SEOPress, o tema usa os campos de SEO para document title, meta description, canonical, Open Graph, Twitter Card e Schema JSON-LD.
- Com um plugin SEO dedicado ativo, o tema evita duplicar as metatags principais.

## Atualizações via GitHub

A partir da versão 4.28.0, o tema pode receber atualizações pelo atualizador nativo do WordPress usando **GitHub Releases**.

1. No WordPress, acesse **DV Visual → Atualizações GitHub** (ou **Aparência → Atualizações GitHub**, dependendo da instalação).
2. Informe o usuário/organização do GitHub e o nome do repositório.
3. Para repositórios privados, informe um fine-grained Personal Access Token com permissão de leitura em **Contents** para esse repositório.
4. Para publicar uma versão, altere a versão em `style.css` e `DINIZ_STUDIO_VERSION` em `functions.php`, faça push e crie uma GitHub Release/tag, por exemplo `v4.29.0`.
5. O WordPress passa a mostrar a nova versão em **Painel → Atualizações** e **Aparência → Temas**.

O pacote ZIP padrão gerado pelo GitHub é normalizado automaticamente pelo updater para manter a pasta estável `dv-visual` e evitar temas duplicados.


### Repositório oficial e autenticação privada (4.28.2+)

O updater é fixado em `Diniz-visual/dv-visual`. Para repositório privado, NÃO coloque o PAT no código do tema nem faça commit dele. Defina o token no `wp-config.php`, antes da linha “That's all, stop editing”: 

```php
define( 'DV_VISUAL_GITHUB_TOKEN', 'github_pat_SEU_NOVO_TOKEN' );
```

Use um Fine-grained PAT com acesso somente ao repositório `dv-visual` e `Contents: Read-only`.
