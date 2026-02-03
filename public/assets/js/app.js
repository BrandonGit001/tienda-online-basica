document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.querySelector(".nav__toggle");
    const menu = document.querySelector("#navMenu");

    if (!toggle || !menu) return;

    toggle.addEventListener("click", () => {
        console.log("¡Click detectado! 🖱️"); // <--- Agrega esto
        const open = menu.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        // AGREGA ESTO: Cambiar el ícono visualmente
        if (open) {
            toggle.innerHTML = "✕"; // Cambia a una X (tache)
        } else {
            toggle.innerHTML = "☰"; // Vuelve a las 3 rayitas (hamburguesa)
        }
    });
});