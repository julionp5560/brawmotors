(() => {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const btnOpen = document.getElementById("btnSidebar");
    const btnClose = document.getElementById("btnSidebarClose");

    const open = () => {
        document.body.classList.add("sidebar-open");
    };
    const close = () => {
        document.body.classList.remove("sidebar-open");
    };

    if (btnOpen) btnOpen.addEventListener("click", open);
    if (btnClose) btnClose.addEventListener("click", close);
    if (overlay) overlay.addEventListener("click", close);

    // cerrar con ESC
    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") close();
    });
})();
