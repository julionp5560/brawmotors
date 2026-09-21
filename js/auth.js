(() => {
    const toggle = document.getElementById("togglePass");
    const pass = document.getElementById("password");
    if (!toggle || !pass) return;

    toggle.addEventListener("click", () => {
        const isPwd = pass.type === "password";
        pass.type = isPwd ? "text" : "password";
        toggle.textContent = isPwd ? "Ocultar" : "Ver";
    });
})();
