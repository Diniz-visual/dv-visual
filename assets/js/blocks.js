(function (blocks, element, blockEditor, components) {
  'use strict';

  var createElement = element.createElement;
  var useBlockProps = blockEditor.useBlockProps;
  var Placeholder = components.Placeholder;

  function editorPreview(title, description, getDetails) {
    return function Edit(props) {
      var details = getDetails ? getDetails(props.attributes || {}) : '';
      return createElement(
        'div',
        useBlockProps({ className: 'dv-block-editor-preview' }),
        createElement(
          Placeholder,
          { icon: 'admin-customizer', label: title },
          createElement('p', null, description),
          details ? createElement('small', null, details) : null
        )
      );
    };
  }

  function register(name, title, description, attributes, getDetails) {
    blocks.registerBlockType(name, {
      apiVersion: 3,
      title: title,
      description: description,
      icon: 'admin-customizer',
      category: 'widgets',
      attributes: attributes || {},
      supports: { html: false, multiple: true },
      edit: editorPreview(title, description, getDetails),
      save: function () { return null; }
    });
  }

  register(
    'dv-visual/services-grid',
    'DV — Grade de soluções',
    'Exibe até quatro Soluções e ativa o Swiper automaticamente no mobile.'
  );
  register(
    'dv-visual/software-carousel',
    'DV — Softwares utilizados',
    'Carrossel Swiper com quatro softwares por vez no desktop, alimentado pelo painel Softwares.'
  );
  register(
    'dv-visual/hero-slider',
    'DV — Hero em carrossel',
    'Hero gerenciável com slides, imagens, botões, métricas e autoplay configurados em DV Visual → Hero e Carrosséis.'
  );
  register(
    'dv-visual/cpt-archive',
    'DV — Arquivo de conteúdo',
    'Listagem completa com busca, filtros, cards e paginação.',
    { postType: { type: 'string', default: '' } },
    function (attributes) { return attributes.postType ? 'Conteúdo: ' + attributes.postType : 'Tipo definido pelo template.'; }
  );
  register(
    'dv-visual/cpt-single',
    'DV — Conteúdo individual',
    'Página interna completa, integrada aos campos ACF e ao tipo de conteúdo atual.'
  );
  register(
    'dv-visual/clients-strip',
    'DV — Carrossel de marcas',
    'Exibe em Swiper todas as marcas publicadas no Custom Post Type Clientes.'
  );
  register(
    'dv-visual/service-list',
    'DV — Lista de soluções',
    'Lista editorial alimentada pelo Custom Post Type Soluções.'
  );
  register(
    'dv-visual/testimonials',
    'DV — Depoimentos',
    'Carrossel dinâmico de depoimentos cadastrados no painel.'
  );
  register(
    'dv-visual/cpt-featured',
    'DV — Conteúdos em destaque',
    'Grade reutilizável para Equipe, Prêmios, Vagas e outros conteúdos.',
    {
      postType: { type: 'string', default: 'product' },
      limit: { type: 'number', default: 3 },
      kicker: { type: 'string', default: '' },
      title: { type: 'string', default: '' }
    },
    function (attributes) {
      return 'Conteúdo: ' + attributes.postType + ' · Limite: ' + attributes.limit;
    }
  );
  register(
    'dv-visual/content-hub',
    'DV — Hub de conteúdo',
    'Integra Produtos, Ferramentas e Guias em uma seção automática.'
  );
  register(
    'dv-visual/portfolio-showcase',
    'DV — Projetos em destaque',
    'Exibe os quatro projetos mais recentes com os mesmos cards da página de Portfólio.'
  );
  register(
    'dv-visual/menu',
    'DV — Menu do WordPress',
    'Exibe o menu atribuído à posição escolhida em Aparência → Menus.',
    { location: { type: 'string', default: 'primary' } },
    function (attributes) { return 'Posição: ' + attributes.location; }
  );
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
