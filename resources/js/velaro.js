// Comportamentos mínimos do design system Velaro. O layout é CSS-first: menus
// e busca do topo são <details>, e funcionam sem esta linha. O que fica aqui é
// só o que CSS não faz: fechar um <details> aberto ao clicar fora dele.
document.addEventListener('click', (ev) => {
  document.querySelectorAll('details.mobile-navigation[open], details.site-nav__mobile[open], details.topbar__search[open]')
    .forEach((d) => { if (!d.contains(ev.target)) d.removeAttribute('open'); });
});
