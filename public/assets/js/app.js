(() => {
  const toggle = document.querySelector(".nav__toggle");
  const menu = document.querySelector("#navMenu");
  if (!toggle || !menu) return;

  toggle.addEventListener("click", () => {
    const open = menu.classList.toggle("is-open");
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
  });
})();
