// js/ui.js
(() => {
    // =========================
    // Helpers
    // =========================
    function escapeHtml(str) {
        return String(str ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function fmtMoney(n) {
        const v = Number(n || 0);
        return v.toFixed(2);
    }

    // =========================
    // Toast
    // =========================
    const toastRoot = (() => {
        let el = document.getElementById("toasts");
        if (!el) {
            el = document.createElement("div");
            el.id = "toasts";
            el.className = "toasts";
            document.body.appendChild(el);
        }
        return el;
    })();

    window.toast = function toast(title, msg, opts = {}) {
        const icon = opts.icon ?? "✓";
        const ttl = opts.ttl ?? 2600;

        const t = document.createElement("div");
        t.className = "toast";
        t.innerHTML = `
      <div class="toast__icon">${escapeHtml(icon)}</div>
      <div>
        <p class="toast__title">${escapeHtml(title)}</p>
        <p class="toast__msg">${escapeHtml(msg)}</p>
      </div>
      <button class="toast__close" type="button" aria-label="Cerrar">✕</button>
    `;

        const close = () => {
            t.style.opacity = "0";
            t.style.transform = "translateY(6px)";
            setTimeout(() => t.remove(), 140);
        };

        t.querySelector(".toast__close").addEventListener("click", close);
        toastRoot.appendChild(t);
        setTimeout(close, ttl);
    };

    // =========================
    // Modal
    // =========================
    window.modalOpen = function modalOpen(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.add("is-open");
        document.body.style.overflow = "hidden";
    };

    window.modalClose = function modalClose(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove("is-open");
        document.body.style.overflow = "";
    };

    document.addEventListener("click", (e) => {
        const closeBtn = e.target.closest("[data-modal-close]");
        if (closeBtn) {
            const id = closeBtn.getAttribute("data-modal-close");
            window.modalClose(id);
            return;
        }

        if (e.target.classList.contains("modal__backdrop")) {
            const modal = e.target.closest(".modal");
            if (modal?.id) window.modalClose(modal.id);
        }
    });

    window.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        const open = document.querySelector(".modal.is-open");
        if (open?.id) window.modalClose(open.id);
    });

    // =========================
    // Servicios cache (para ventas)
    // =========================
    let SERVICIOS = [];

    async function loadServicios(tipo = "") {
        const qs = tipo ? `?tipo=${encodeURIComponent(tipo)}` : "";
        const res = await fetch(`php/api/servicios/list.php${qs}`);
        const j = await res.json();
        if (!j.ok) throw new Error(j.error || "No se pudieron cargar servicios");
        SERVICIOS = Array.isArray(j.data) ? j.data : [];
        return SERVICIOS;
    }

    function serviciosOptionsHTML() {
        const opts = SERVICIOS.map((s) => {
            const id = s.id;
            const nombre = s.nombre ?? "";
            const tipo = s.tipo ?? "";
            const precio = Number(s.precio ?? 0);
            const desc = s.descripcion ?? "";

            return `<option
        value="${escapeHtml(id)}"
        data-precio="${precio}"
        data-nombre="${escapeHtml(nombre)}"
        data-desc="${escapeHtml(desc)}"
        data-tipo="${escapeHtml(tipo)}"
      >${escapeHtml(nombre)} — $${precio.toFixed(2)} (${escapeHtml(tipo)})</option>`;
        }).join("");

        return `<option value="">-- Elegir servicio --</option>${opts}`;
    }

    // =========================
    // Ventas: UI del modal (items, recalc)
    // =========================
    function initVentaModalUI() {
        const btnNueva = document.getElementById("btnNuevaVenta");
        const btnAdd = document.getElementById("btnAddItem");
        const tbody = document.getElementById("ventaItems");
        const ivaInput = document.getElementById("ivaRate");

        if (!btnNueva || !btnAdd || !tbody) return; // no estamos en ventas

        btnNueva.addEventListener("click", async () => {
            try {
                await loadServicios(); // carga todos
            } catch (e) {
                toast("Error", "No pude cargar servicios.", { icon: "⚠️", ttl: 3500 });
                SERVICIOS = [];
            }

            if (tbody.children.length === 0) addVentaRow(tbody);
            recalcVenta();
            modalOpen("modalVenta");
        });

        btnAdd.addEventListener("click", () => {
            addVentaRow(tbody);
            recalcVenta();
        });

        tbody.addEventListener("input", () => recalcVenta());

        tbody.addEventListener("click", (e) => {
            const rm = e.target.closest("[data-remove]");
            if (!rm) return;
            rm.closest("tr").remove();
            recalcVenta();
        });

        tbody.addEventListener("change", (e) => {
            const sel = e.target.closest(".servicioSel");
            if (!sel) return;

            const opt = sel.options[sel.selectedIndex];
            const precio = Number(opt?.getAttribute("data-precio") ?? 0);
            const nombre = opt?.getAttribute("data-nombre") ?? "";
            const desc = opt?.getAttribute("data-desc") ?? "";
            const tipo = opt?.getAttribute("data-tipo") ?? "";

            const tr = sel.closest("tr");
            const precioInp = tr.querySelector(".precioInp");
            if (precioInp) precioInp.value = precio.toFixed(2);

            const info = tr.querySelector("[data-info]");
            if (info) info.textContent = nombre ? `(${tipo}) ${desc}` : "";

            recalcVenta();
        });

        ivaInput?.addEventListener("input", () => recalcVenta());
    }

    function addVentaRow(tbodyEl) {
        const row = document.createElement("tr");
        row.innerHTML = `
      <td>
        <select class="select servicioSel">
          ${serviciosOptionsHTML()}
        </select>
        <div class="xs muted" style="margin-top:6px;" data-info></div>
      </td>
      <td class="num">
        <input class="input2 qtyInp" type="number" value="1" min="1" step="1" style="text-align:right"/>
      </td>
      <td class="num">
        <input class="input2 precioInp" type="number" value="0" min="0" step="0.01" style="text-align:right"/>
      </td>
      <td class="num"><span class="lineTotal">0.00</span></td>
      <td class="num">
        <button class="btn-sm" type="button" data-remove>Quitar</button>
      </td>
    `;
        tbodyEl.appendChild(row);
    }

    function recalcVenta() {
        const rows = Array.from(document.querySelectorAll("#ventaItems tr"));
        let subtotal = 0;

        for (const r of rows) {
            const qty = Number(r.querySelector(".qtyInp")?.value ?? 0);
            const price = Number(r.querySelector(".precioInp")?.value ?? 0);
            const line = qty * price;

            const lt = r.querySelector(".lineTotal");
            if (lt) lt.textContent = line.toFixed(2);

            subtotal += line;
        }

        const ivaRate = Number(document.getElementById("ivaRate")?.value ?? 0);
        const iva = subtotal * (ivaRate / 100);
        const total = subtotal + iva;

        setText("sumSubtotal", subtotal);
        setText("sumIva", iva);
        setText("sumTotal", total);
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = Number(val).toFixed(2);
    }

    // =========================
    // Ventas: tabla (list.php)
    // =========================
    function initVentasTable() {
        const ventasTbody = document.getElementById("ventasTbody");
        const ventasCount = document.getElementById("ventasCount");
        const ventasSearch = document.getElementById("ventasSearch");

        if (!ventasTbody) return; // no estamos en ventas

        let VENTAS = [];
        let q = "";

        ventasSearch?.addEventListener("input", () => {
            q = (ventasSearch.value || "").toLowerCase();
            renderVentas();
        });

        async function loadVentas() {
            ventasTbody.innerHTML = `<tr><td colspan="7" class="muted">Cargando…</td></tr>`;
            try {
                const res = await fetch("php/api/ventas/list.php");
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudieron cargar ventas");
                VENTAS = Array.isArray(j.data) ? j.data : [];
                renderVentas();
            } catch (e) {
                ventasTbody.innerHTML = `<tr><td colspan="7" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
                toast("Error", "No pude cargar ventas.", { icon: "⚠️", ttl: 3500 });
            }
        }

        function renderVentas() {
            const rows = VENTAS.filter((v) => {
                if (!q) return true;
                const txt = `${v.folio} ${v.fecha} ${v.cliente_nombre} ${v.metodo_pago} ${v.estado}`.toLowerCase();
                return txt.includes(q);
            });

            if (ventasCount) ventasCount.textContent = `${rows.length} ventas`;

            if (rows.length === 0) {
                ventasTbody.innerHTML = `<tr><td colspan="7" class="muted">Sin ventas.</td></tr>`;
                return;
            }

            ventasTbody.innerHTML = rows
                .map((v) => {
                    const total = fmtMoney(v.total);
                    const estado = v.estado || "pagada";
                    const tag = estado === "pagada" ? "tag--ok" : "tag--bad";

                    return `
            <tr>
              <td><b>${escapeHtml(v.folio || "")}</b></td>
              <td>${escapeHtml(v.fecha || "")}</td>
              <td>${escapeHtml(v.cliente_nombre || "")}</td>
              <td>${escapeHtml(v.metodo_pago || "")}</td>
              <td class="num">$${total}</td>
              <td><span class="tag ${tag}">${escapeHtml(estado)}</span></td>
              <td class="num">
                <div class="row-actions">
                  <button class="btn-sm" type="button" onclick="window.open('ticket.php?id=${Number(v.id)}','_blank')">Ver</button>
                  <button class="btn-sm" type="button" onclick="window.open('ticket.php?id=${Number(v.id)}','_blank')">Print</button>
                   ${(v.estado === "cancelada")
                            ? `<button class="btn-sm" type="button" disabled>Cancelada</button>`
                            : `<button class="btn-sm" type="button" onclick="window.openCancelarVenta(${Number(v.id)})">Cancelar</button>`
                        }
                </div>
              </td>
            </tr>
          `;
                })
                .join("");
        }

        // Exponer para refrescar cuando cobras
        window.__reloadVentas = loadVentas;

        // Primera carga
        loadVentas();
    }

    // =========================
    // Ventas: Cobrar (create.php) -> ticket.php?id=...
    // (blindado contra listeners duplicados)
    // =========================
    function initCobrarHandler() {
        const btnCobrar = document.getElementById("btnCobrar");
        const tbody = document.getElementById("ventaItems");
        if (!btnCobrar || !tbody) return;

        // Clon para eliminar listeners viejos si existían
        const cleanBtn = btnCobrar.cloneNode(true);
        btnCobrar.parentNode.replaceChild(cleanBtn, btnCobrar);

        cleanBtn.addEventListener("click", async () => {
            const rows = Array.from(document.querySelectorAll("#ventaItems tr"));

            const items = rows
                .map((r) => {
                    const sel = r.querySelector(".servicioSel");
                    const opt = sel?.options?.[sel.selectedIndex];

                    const servicio_id = Number(sel?.value || 0);
                    const desc = opt?.getAttribute("data-nombre") || "Servicio";
                    const qty = Number(r.querySelector(".qtyInp")?.value ?? 1);
                    const price = Number(r.querySelector(".precioInp")?.value ?? 0);

                    return { servicio_id, desc, qty, price };
                })
                .filter((x) => x.qty > 0 && x.price >= 0);

            if (items.length === 0) {
                toast("Falta items", "Agrega al menos un servicio.", { icon: "⚠️", ttl: 3200 });
                return;
            }

            const iva_rate = Number(document.getElementById("ivaRate")?.value ?? 0);
            const cliente = document.getElementById("ventaCliente")?.value ?? "Mostrador";
            const pago = document.getElementById("ventaPago")?.value ?? "efectivo";
            const notas = document.getElementById("ventaNotas")?.value ?? "";

            const payload = { cliente, pago, notas, iva_rate, items };

            try {
                const res = await fetch("php/api/ventas/create.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload),
                });

                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo guardar la venta");
                if (!j.venta_id) throw new Error("La API no devolvió venta_id");

                modalClose("modalVenta");
                toast("Venta guardada", `Folio ${j.folio}`, { icon: "🧾" });

                window.open(`ticket.php?id=${j.venta_id}`, "_blank");

                if (typeof window.__reloadVentas === "function") window.__reloadVentas();
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        });
    }

    // =========================
    // Servicios: CRUD pantalla servicios.php (list + create)
    // =========================
    function initServiciosCRUD() {
        const tbody = document.getElementById("serviciosTbody");
        const btnNuevo = document.getElementById("btnNuevoServicio");
        const btnGuardar = document.getElementById("btnGuardarServicio");
        const search = document.getElementById("serviciosSearch");
        const filtroTipo = document.getElementById("serviciosFiltroTipo");
        const mostrarInactivos = document.getElementById("serviciosMostrarInactivos");
        const count = document.getElementById("serviciosCount");

        if (!tbody || !btnNuevo || !btnGuardar) return; // no estamos en servicios

        const state = { rows: [], q: "", tipo: "" };

        btnNuevo.addEventListener("click", () => {
            openServicioModal(null);
        });

        btnGuardar.addEventListener("click", saveServicio);

        tbody.addEventListener("click", (e) => {
            const btnEdit = e.target.closest("[data-srv-edit]");
            if (btnEdit) {
                const id = Number(btnEdit.getAttribute("data-srv-edit"));
                const row = state.rows.find((r) => Number(r.id) === id);
                if (row) openServicioModal(row);
                return;
            }
            const btnDel = e.target.closest("[data-srv-del]");
            if (btnDel) {
                deleteServicio(Number(btnDel.getAttribute("data-srv-del")), btnDel.getAttribute("data-srv-nombre") || "");
            }
        });

        search?.addEventListener("input", () => {
            state.q = (search.value || "").toLowerCase();
            render();
        });

        filtroTipo?.addEventListener("change", () => {
            state.tipo = filtroTipo.value || "";
            load();
        });

        mostrarInactivos?.addEventListener("change", () => load());

        load();

        async function load() {
            tbody.innerHTML = `<tr><td colspan="7" class="muted">Cargando…</td></tr>`;
            try {
                const params = new URLSearchParams();
                if (state.tipo) params.set("tipo", state.tipo);
                if (mostrarInactivos?.checked) params.set("incluir_inactivos", "1");
                const qs = params.toString() ? `?${params.toString()}` : "";
                const res = await fetch(`php/api/servicios/list.php${qs}`);
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudieron cargar servicios");
                state.rows = Array.isArray(j.data) ? j.data : [];
                render();
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
                toast("Error", "No pude cargar servicios.", { icon: "⚠️", ttl: 3500 });
            }
        }

        function render() {
            const q = state.q;
            const rows = state.rows.filter((r) => {
                if (!q) return true;
                const text = `${r.nombre || ""} ${r.descripcion || ""} ${r.tipo || ""}`.toLowerCase();
                return text.includes(q);
            });

            if (count) count.textContent = `${rows.length} items`;

            if (rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="muted">Sin resultados.</td></tr>`;
                return;
            }

            tbody.innerHTML = rows
                .map((r) => {
                    const tipo = r.tipo || "";
                    const precioNum = Number(r.precio || 0);
                    const precio = precioNum.toFixed(2);
                    const tiempo = Number(r.tiempo_estimado || 0);
                    const activoTxt = (r.activo == 1 || r.activo === "1") ? "Activo" : "Inactivo";
                    const tagClass = activoTxt === "Activo" ? "tag--ok" : "tag--bad";

                    // costo_insumos viene redactado (undefined) para el rol operario;
                    // en ese caso no mostramos la columna ni la alerta de pérdida.
                    const tieneCosto = r.costo_insumos !== undefined && r.costo_insumos !== null;
                    const costoNum = tieneCosto ? Number(r.costo_insumos) : 0;
                    const enPerdida = tieneCosto && costoNum > 0 && precioNum < costoNum;
                    const costoTxt = tieneCosto
                        ? `$${costoNum.toFixed(2)}${enPerdida ? ' <span class="tag tag--bad" title="El precio de venta es menor al costo de los insumos">⚠️ bajo costo</span>' : ""}`
                        : "—";

                    return `
            <tr>
              <td>
                <b>${escapeHtml(r.nombre || "")}</b>
                <div class="xs muted" style="margin-top:4px;">${escapeHtml(r.descripcion || "")}</div>
              </td>
              <td>${escapeHtml(tipo)}</td>
              <td class="num">$${precio}</td>
              <td class="num">${costoTxt}</td>
              <td class="num">${tiempo} min</td>
              <td><span class="tag ${tagClass}">${activoTxt}</span></td>
              <td class="num">
                <div class="row-actions">
                  <button class="btn-sm" type="button" data-srv-edit="${r.id}">Editar</button>
                  <button class="btn-sm" type="button" data-srv-del="${r.id}" data-srv-nombre="${escapeHtml(r.nombre || "")}">Eliminar</button>
                </div>
              </td>
            </tr>
          `;
                })
                .join("");
        }

        function toggleInsumosBloque(activo) {
            const bloque = document.getElementById("srvInsumosBloque");
            const vacio = document.getElementById("srvInsumosBloqueVacio");
            if (bloque) bloque.style.display = activo ? "" : "none";
            if (vacio) vacio.style.display = activo ? "none" : "";
        }

        function openServicioModal(row) {
            const title = document.getElementById("srvModalTitle");
            const set = (id, v) => {
                const el = document.getElementById(id);
                if (el) el.value = v;
            };

            if (!row) {
                if (title) title.textContent = "Nuevo servicio";
                set("srvId", "0");
                set("srvNombre", "");
                set("srvDesc", "");
                set("srvTipo", "autolavado");
                set("srvPrecio", "0");
                set("srvTiempo", "0");
                set("srvActivo", "1");
                toggleInsumosBloque(false);
            } else {
                if (title) title.textContent = "Editar servicio";
                set("srvId", String(row.id));
                set("srvNombre", row.nombre || "");
                set("srvDesc", row.descripcion || "");
                set("srvTipo", row.tipo || "autolavado");
                set("srvPrecio", Number(row.precio || 0));
                set("srvTiempo", Number(row.tiempo_estimado || 0));
                set("srvActivo", String(Number(row.activo ?? 1)));
                toggleInsumosBloque(true);
                loadInsumosProductosSelect().then(() => loadInsumos(row.id));
            }

            modalOpen("modalServicio");
            setTimeout(() => document.getElementById("srvNombre")?.focus(), 60);
        }

        // =========================
        // Insumos del servicio (dentro del mismo modal)
        // =========================
        let insumosProductosCache = null;

        async function loadInsumosProductosSelect() {
            const sel = document.getElementById("srvInsumoProducto");
            if (!sel) return;
            try {
                if (!insumosProductosCache) {
                    const res = await fetch(`php/api/productos/list.php`);
                    const j = await res.json();
                    insumosProductosCache = j.ok && Array.isArray(j.data) ? j.data : [];
                }
                sel.innerHTML = insumosProductosCache
                    .map((p) => `<option value="${p.id}">${escapeHtml(p.nombre || "")} ($${Number(p.costo || 0).toFixed(2)})</option>`)
                    .join("") || `<option value="">Sin productos</option>`;
            } catch (e) {
                sel.innerHTML = `<option value="">Error al cargar productos</option>`;
            }
        }

        async function loadInsumos(servicioId) {
            const tbody2 = document.getElementById("srvInsumosTbody");
            const totalEl = document.getElementById("srvInsumosCostoTotal");
            if (!tbody2) return;
            tbody2.innerHTML = `<tr><td colspan="5" class="muted">Cargando…</td></tr>`;
            try {
                const res = await fetch(`php/api/servicios/insumos/list.php?servicio_id=${servicioId}`);
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudieron cargar los insumos");

                const rows = Array.isArray(j.data) ? j.data : [];
                if (rows.length === 0) {
                    tbody2.innerHTML = `<tr><td colspan="5" class="muted">Sin insumos agregados.</td></tr>`;
                } else {
                    tbody2.innerHTML = rows
                        .map((r) => {
                            const inactivo = Number(r.producto_activo) !== 1;
                            return `
                <tr>
                  <td>${escapeHtml(r.producto_nombre || "")}${inactivo ? ' <span class="tag tag--bad">inactivo</span>' : ""}</td>
                  <td class="num">${Number(r.cantidad).toString()}</td>
                  <td class="num">$${Number(r.producto_costo).toFixed(2)}</td>
                  <td class="num">$${Number(r.subtotal).toFixed(2)}</td>
                  <td class="num"><button class="btn-sm" type="button" data-insumo-del="${r.id}">Quitar</button></td>
                </tr>
              `;
                        })
                        .join("");
                }

                if (totalEl) totalEl.textContent = `$${Number(j.costo_total || 0).toFixed(2)}`;
            } catch (e) {
                tbody2.innerHTML = `<tr><td colspan="5" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
            }
        }

        document.getElementById("btnAgregarInsumo")?.addEventListener("click", async () => {
            const servicioId = Number(document.getElementById("srvId")?.value || 0);
            const productoId = Number(document.getElementById("srvInsumoProducto")?.value || 0);
            const cantidad = Number(document.getElementById("srvInsumoCantidad")?.value || 0);

            if (!servicioId) return;
            if (!productoId) {
                toast("Falta producto", "Selecciona un producto.", { icon: "⚠️", ttl: 3000 });
                return;
            }
            if (!(cantidad > 0)) {
                toast("Cantidad inválida", "La cantidad debe ser mayor a 0.", { icon: "⚠️", ttl: 3000 });
                return;
            }

            try {
                const res = await fetch("php/api/servicios/insumos/add.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ servicio_id: servicioId, producto_id: productoId, cantidad }),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo agregar el insumo");

                await loadInsumos(servicioId);
                await load(); // refresca costo_insumos en la tabla principal
                toast("Agregado", "Insumo agregado al servicio ✅", { icon: "✓", ttl: 2200 });
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        });

        document.getElementById("srvInsumosTbody")?.addEventListener("click", async (e) => {
            const btn = e.target.closest("[data-insumo-del]");
            if (!btn) return;
            const id = Number(btn.getAttribute("data-insumo-del"));
            const servicioId = Number(document.getElementById("srvId")?.value || 0);
            if (!id) return;

            try {
                const res = await fetch("php/api/servicios/insumos/remove.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id }),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo quitar el insumo");

                await loadInsumos(servicioId);
                await load();
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        });

        async function saveServicio() {
            const id = Number(document.getElementById("srvId")?.value || 0);
            const nombre = (document.getElementById("srvNombre")?.value || "").trim();
            const descripcion = (document.getElementById("srvDesc")?.value || "").trim();
            const tipo = document.getElementById("srvTipo")?.value || "";
            const precio = Number(document.getElementById("srvPrecio")?.value || 0);
            const tiempo_estimado = Number(document.getElementById("srvTiempo")?.value || 0);
            const activo = Number(document.getElementById("srvActivo")?.value || 1);

            if (!nombre) {
                toast("Falta nombre", "Ponle nombre al servicio.", { icon: "⚠️", ttl: 3200 });
                return;
            }
            if (!["taller", "autolavado"].includes(tipo)) {
                toast("Tipo inválido", "Debe ser taller o autolavado.", { icon: "⚠️", ttl: 3200 });
                return;
            }
            if (precio < 0) {
                toast("Precio inválido", "El precio no puede ser negativo.", { icon: "⚠️", ttl: 3200 });
                return;
            }

            const url = id > 0 ? "php/api/servicios/update.php" : "php/api/servicios/create.php";
            const payload = id > 0
                ? { id, nombre, descripcion, tipo, precio, tiempo_estimado, activo }
                : { nombre, descripcion, tipo, precio, tiempo_estimado, activo };

            try {
                const res = await fetch(url, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo guardar");

                modalClose("modalServicio");
                toast("Guardado", id > 0 ? "Servicio actualizado ✅" : "Servicio agregado ✅", { icon: "✓" });

                await load();
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        }

        async function deleteServicio(id, nombre) {
            if (!id) return;
            const ok = window.confirm(`¿Eliminar el servicio "${nombre}"? Si ya se usó en alguna venta u orden, en vez de borrarlo se marcará como inactivo.`);
            if (!ok) return;

            try {
                const res = await fetch("php/api/servicios/delete.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id }),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo eliminar");

                toast("Listo", j.mode === "soft" ? "Servicio marcado como inactivo." : "Servicio eliminado.", { icon: "✓" });
                await load();
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        }
    }

    // =========================
    // Home: Ventas hoy + Corte hoy
    // =========================
    window.addEventListener("DOMContentLoaded", async () => {
        const elVentasTotal = document.getElementById("homeVentasTotal");
        const elVentasCount = document.getElementById("homeVentasCount");
        const elCorteTotal = document.getElementById("homeCorteTotal");
        const elCorteBreakdown = document.getElementById("homeCorteBreakdown");

        // Si no estamos en Home, salimos
        if (!elVentasTotal || !elVentasCount || !elCorteTotal || !elCorteBreakdown) return;

        try {
            const res = await fetch("php/api/ventas/resumen_hoy.php");
            const j = await res.json();
            if (!j.ok) throw new Error(j.error || "No pude cargar resumen");

            const n = Number(j.ventas?.n ?? 0);
            const total = Number(j.ventas?.total ?? 0);

            elVentasTotal.textContent = `$${total.toFixed(2)}`;
            elVentasCount.textContent = `${n} venta${n === 1 ? "" : "s"}`;

            const c = j.corte || {};
            const e = Number(c.efectivo?.total ?? 0);
            const t = Number(c.tarjeta?.total ?? 0);
            const tr = Number(c.transferencia?.total ?? 0);
            const corteTotal = e + t + tr;

            elCorteTotal.textContent = `$${corteTotal.toFixed(2)}`;
            elCorteBreakdown.textContent =
                `Efectivo $${e.toFixed(2)} • Tarjeta $${t.toFixed(2)} • Transferencia $${tr.toFixed(2)}`;

        } catch (e) {
            console.error(e);
            // no spameamos toast en home, solo deja los defaults
        }
    });
    // =========================
    // Cancelar venta (UI + API)
    // =========================
    window.addEventListener("DOMContentLoaded", () => {
        const btn = document.getElementById("btnConfirmarCancelacion");
        const inId = document.getElementById("cancelVentaId");
        const inMotivo = document.getElementById("cancelMotivo");

        // si no existe el modal, no estamos en ventas
        if (!btn || !inId || !inMotivo) return;

        // abrir modal desde tabla
        window.openCancelarVenta = (id) => {
            inId.value = String(id);
            inMotivo.value = "";
            modalOpen("modalCancelarVenta");
            setTimeout(() => inMotivo.focus(), 60);
        };

        btn.addEventListener("click", async () => {
            const id = Number(inId.value || 0);
            const motivo = (inMotivo.value || "").trim();

            if (!id) {
                toast("Error", "ID inválido.", { icon: "⚠️", ttl: 3200 });
                return;
            }
            if (!motivo) {
                toast("Falta motivo", "Escribe el motivo de cancelación.", { icon: "⚠️", ttl: 3200 });
                return;
            }

            try {
                const res = await fetch("php/api/ventas/cancel.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id, motivo }),
                });

                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo cancelar");

                modalClose("modalCancelarVenta");
                toast("Cancelada", "La venta fue cancelada ✅", { icon: "🧾" });

                // refrescar tabla y (si estás en home) refrescar resumen en la próxima recarga
                if (typeof window.__reloadVentas === "function") window.__reloadVentas();
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        });
    });
    // =========================
    // Corte de caja (p=corte)
    // =========================
    window.addEventListener("DOMContentLoaded", () => {
        const dateInp = document.getElementById("corteDate");
        const btnLoad = document.getElementById("btnCargarCorte");
        const btnPrint = document.getElementById("btnImprimirCorte");
        const btnClose = document.getElementById("btnCerrarCaja");
        const btnReporte = document.getElementById("btnVerReporte");
        const btnCortePdf = document.getElementById("btnCortePdf");


        const pill = document.getElementById("corteFechaPill");
        const elTotal = document.getElementById("corteTotal");
        const elN = document.getElementById("corteNventas");
        const elSub = document.getElementById("corteSubtotal");
        const elIva = document.getElementById("corteIva");
        const elCanN = document.getElementById("corteCanceladasN");
        const elCanT = document.getElementById("corteCanceladasTotal");
        const elTop = document.getElementById("corteMetodoTop");

        const tbody = document.getElementById("corteTbody");

        // si no estamos en la página corte, salimos
        if (!dateInp || !btnLoad || !btnPrint || !tbody) return;

        // default hoy
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInp.value = `${yyyy}-${mm}-${dd}`;

        btnLoad.addEventListener("click", () => loadCorte(dateInp.value));
        btnPrint.addEventListener("click", () => window.print());
        btnClose?.addEventListener("click", async () => {
            const dateStr = dateInp.value;
            if (!confirm(`¿Cerrar caja del ${dateStr}? Esto "congela" las ventas del día.`)) return;

            try {
                const res = await fetch("php/api/cortes/close.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ date: dateStr }),
                });

                const text = await res.text();
                if (!res.ok) throw new Error(text.slice(0, 160));
                const j = JSON.parse(text);

                if (!j.ok) throw new Error(j.error || "No se pudo cerrar caja");

                toast("Caja cerrada", `Folio ${j.folio}`, { icon: "🔒" });

                // recargar corte para mostrar ya "congelado"
                await loadCorte(dateStr);
            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        });

        btnReporte?.addEventListener("click", () => {
            const dateStr = dateInp.value;
            window.open(`dashboard.php?p=reporte&date=${encodeURIComponent(dateStr)}`, "_blank");
        });

        btnCortePdf?.addEventListener("click", () => {
            window.open(`php/api/reportes/corte_pdf.php?date=${encodeURIComponent(dateInp.value)}`, "_blank");
        });


        loadCorte(dateInp.value);

        async function loadCorte(dateStr) {
            tbody.innerHTML = `<tr><td colspan="3" class="muted">Cargando…</td></tr>`;
            try {
                const res = await fetch(`php/api/ventas/corte.php?date=${encodeURIComponent(dateStr)}`);
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo cargar corte");

                if (pill) pill.textContent = j.date || dateStr;

                const pag = j.pagadas || {};
                const met = j.metodos || {};
                const can = j.canceladas || {};

                const total = Number(pag.total || 0);
                const subtotal = Number(pag.subtotal || 0);
                const iva = Number(pag.iva || 0);
                const n = Number(pag.n || 0);

                if (elTotal) elTotal.textContent = `$${total.toFixed(2)}`;
                if (elSub) elSub.textContent = `$${subtotal.toFixed(2)}`;
                if (elIva) elIva.textContent = `$${iva.toFixed(2)} IVA`;
                if (elN) elN.textContent = `${n} venta${n === 1 ? "" : "s"}`;

                const cn = Number(can.n || 0);
                const ct = Number(can.total || 0);
                if (elCanN) elCanN.textContent = String(cn);
                if (elCanT) elCanT.textContent = `$${ct.toFixed(2)}`;

                const order = ["efectivo", "tarjeta", "transferencia"];
                let topName = "-";
                let topVal = -1;

                tbody.innerHTML = order.map(k => {
                    const row = met[k] || { n: 0, total: 0 };
                    const nn = Number(row.n || 0);
                    const tt = Number(row.total || 0);
                    if (tt > topVal) { topVal = tt; topName = k; }

                    return `
            <tr>
              <td>${escapeHtml(k)}</td>
              <td class="num">${nn}</td>
              <td class="num">$${tt.toFixed(2)}</td>
            </tr>
          `;
                }).join("");

                if (elTop) elTop.textContent = `${topName} $${Math.max(0, topVal).toFixed(2)}`;

            } catch (e) {
                console.error(e);
                tbody.innerHTML = `<tr><td colspan="3" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
                toast("Error", "No pude cargar el corte.", { icon: "⚠️", ttl: 3500 });
            }
        }
    });
    // =========================
    // Reporte del día (p=reporte)
    // =========================
    window.addEventListener("DOMContentLoaded", async () => {
        const repFecha = document.getElementById("repFecha");
        const repTotal = document.getElementById("repTotal");
        const repN = document.getElementById("repN");
        const repTop = document.getElementById("repTop");
        const repVentas = document.getElementById("repVentas");

        if (!repFecha || !repTotal || !repN || !repTop || !repVentas) return;

        const params = new URLSearchParams(location.search);
        const date = params.get("date") || (() => {
            const d = new Date();
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        })();

        repFecha.textContent = date;

        try {
            const res = await fetch(`php/api/cortes/reporte_dia.php?date=${encodeURIComponent(date)}`);
            const text = await res.text();
            if (!res.ok) throw new Error(text.slice(0, 160));
            const j = JSON.parse(text);
            if (!j.ok) throw new Error(j.error || "No se pudo cargar reporte");

            const n = Number(j.summary?.n ?? 0);
            const total = Number(j.summary?.total ?? 0);
            repTotal.textContent = `$${total.toFixed(2)}`;
            repN.textContent = `${n} venta${n === 1 ? '' : 's'}`;

            // Top servicios
            const top = Array.isArray(j.top_servicios) ? j.top_servicios : [];
            repTop.innerHTML = top.length ? top.map(x => `
        <tr>
          <td>${escapeHtml(x.descripcion || "")}</td>
          <td class="num">${Number(x.qty || 0)}</td>
          <td class="num">$${Number(x.total || 0).toFixed(2)}</td>
        </tr>
      `).join("") : `<tr><td colspan="3" class="muted">Sin datos.</td></tr>`;

            // Ventas
            const ventas = Array.isArray(j.ventas) ? j.ventas : [];
            repVentas.innerHTML = ventas.length ? ventas.map(v => {
                const dt = String(v.fecha || "");
                const hora = dt.includes(" ") ? dt.split(" ")[1].slice(0, 5) : dt;
                return `
          <tr>
            <td><b>${escapeHtml(v.folio || "")}</b></td>
            <td>${escapeHtml(hora)}</td>
            <td>${escapeHtml(v.cliente_nombre || "")}</td>
            <td>${escapeHtml(v.metodo_pago || "")}</td>
            <td class="num">$${Number(v.total || 0).toFixed(2)}</td>
            <td>${escapeHtml(v.estado || "")}</td>
          </tr>
        `;
            }).join("") : `<tr><td colspan="6" class="muted">Sin ventas.</td></tr>`;

        } catch (e) {
            repTop.innerHTML = `<tr><td colspan="3" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
            repVentas.innerHTML = `<tr><td colspan="6" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
        }
    });
    // =========================
    // Reportes por rango (p=reportes)
    // =========================
    window.addEventListener("DOMContentLoaded", () => {
        const pill = document.getElementById("repRangoPill");
        const inpDesde = document.getElementById("repDesde");
        const inpHasta = document.getElementById("repHasta");
        const btnGenerar = document.getElementById("btnGenerarReporte");
        const btnPdf = document.getElementById("btnReporteVentasPdf");
        const serieTbody = document.getElementById("repSerieTbody");
        const topServTbody = document.getElementById("repTopServiciosTbody");
        const topCliTbody = document.getElementById("repTopClientesTbody");

        const elVentasTotal = document.getElementById("repVentasTotal");
        const elVentasN = document.getElementById("repVentasN");
        const elTallerTotal = document.getElementById("repTallerTotal");
        const elTallerN = document.getElementById("repTallerN");
        const elTotalGeneral = document.getElementById("repTotalGeneral");

        if (!pill || !inpDesde || !inpHasta || !btnGenerar || !serieTbody || !topServTbody || !topCliTbody) return;

        function fmtDate(d) {
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        // Rango por defecto: últimos 7 días
        const hoy = new Date();
        const hace7 = new Date();
        hace7.setDate(hoy.getDate() - 6);
        inpDesde.value = fmtDate(hace7);
        inpHasta.value = fmtDate(hoy);

        async function cargarReporte() {
            const desde = inpDesde.value;
            const hasta = inpHasta.value;
            pill.textContent = `${desde} → ${hasta}`;

            serieTbody.innerHTML = `<tr><td colspan="3" class="muted">Cargando…</td></tr>`;
            topServTbody.innerHTML = `<tr><td colspan="3" class="muted">Cargando…</td></tr>`;
            topCliTbody.innerHTML = `<tr><td colspan="3" class="muted">Cargando…</td></tr>`;

            try {
                const res = await fetch(`php/api/reportes/rango.php?desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}`);
                const text = await res.text();
                if (!res.ok) throw new Error(text.slice(0, 160));
                const j = JSON.parse(text);
                if (!j.ok) throw new Error(j.error || "No se pudo generar el reporte");

                pill.textContent = `${j.desde} → ${j.hasta}`;

                const s = j.summary || {};
                if (elVentasTotal) elVentasTotal.textContent = `$${Number(s.ventas_total || 0).toFixed(2)}`;
                if (elVentasN) elVentasN.textContent = `${Number(s.ventas_n || 0)} venta${Number(s.ventas_n || 0) === 1 ? '' : 's'}`;
                if (elTallerTotal) elTallerTotal.textContent = `$${Number(s.taller_total || 0).toFixed(2)}`;
                if (elTallerN) elTallerN.textContent = `${Number(s.taller_n || 0)} orden${Number(s.taller_n || 0) === 1 ? '' : 'es'}`;
                if (elTotalGeneral) elTotalGeneral.textContent = `$${Number(s.total_general || 0).toFixed(2)}`;

                const serie = Array.isArray(j.serie_diaria) ? j.serie_diaria : [];
                serieTbody.innerHTML = serie.length ? serie.map(r => `
          <tr>
            <td>${escapeHtml(r.dia || "")}</td>
            <td class="num">${Number(r.n || 0)}</td>
            <td class="num">$${Number(r.total || 0).toFixed(2)}</td>
          </tr>
        `).join("") : `<tr><td colspan="3" class="muted">Sin ventas en este rango.</td></tr>`;

                const topServ = Array.isArray(j.top_servicios) ? j.top_servicios : [];
                topServTbody.innerHTML = topServ.length ? topServ.map(r => `
          <tr>
            <td>${escapeHtml(r.descripcion || "")}</td>
            <td class="num">${Number(r.qty || 0)}</td>
            <td class="num">$${Number(r.total || 0).toFixed(2)}</td>
          </tr>
        `).join("") : `<tr><td colspan="3" class="muted">Sin datos.</td></tr>`;

                const topCli = Array.isArray(j.top_clientes) ? j.top_clientes : [];
                topCliTbody.innerHTML = topCli.length ? topCli.map(r => `
          <tr>
            <td>${escapeHtml(r.cliente || "")}</td>
            <td class="num">${Number(r.visitas || 0)}</td>
            <td class="num">$${Number(r.total || 0).toFixed(2)}</td>
          </tr>
        `).join("") : `<tr><td colspan="3" class="muted">Sin datos.</td></tr>`;

            } catch (e) {
                serieTbody.innerHTML = `<tr><td colspan="3" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
                topServTbody.innerHTML = `<tr><td colspan="3" class="muted">—</td></tr>`;
                topCliTbody.innerHTML = `<tr><td colspan="3" class="muted">—</td></tr>`;
                toast("Error", "No pude generar el reporte.", { icon: "⚠️", ttl: 3500 });
            }
        }

        btnGenerar.addEventListener("click", cargarReporte);

        btnPdf?.addEventListener("click", () => {
            const desde = encodeURIComponent(inpDesde.value);
            const hasta = encodeURIComponent(inpHasta.value);
            window.open(`php/api/reportes/ventas_rango_pdf.php?desde=${desde}&hasta=${hasta}`, "_blank");
        });
        cargarReporte();
    });
    // =========================
    // Inventario (p=inventario) - CORREGIDO
    // =========================
    window.addEventListener("DOMContentLoaded", () => {
        const invTbody = document.getElementById("invTbody");
        const movTbody = document.getElementById("movTbody");

        if (!invTbody || !movTbody) return; // no estamos en inventario

        const invSearch = document.getElementById("invSearch");
        const invSoloBajo = document.getElementById("invSoloBajo");
        const invMostrarInactivos = document.getElementById("invMostrarInactivos");
        const invCount = document.getElementById("invCount");
        const invBajoCount = document.getElementById("invBajoCount");

        const btnCategorias = document.getElementById("btnCategorias");
        const catNombre = document.getElementById("catNombre");
        const btnCatCrear = document.getElementById("btnCatCrear");
        const catTbody = document.getElementById("catTbody");
        let CATEGORIAS = [];

        // =========================
        // Categorías -> llenar SELECT del modal producto (a prueba de fallos)
        // =========================
        async function fillProdCategoriasSelect(selectedId = "") {
            const sel = document.querySelector("#modalProducto #prodCategoriaId")

            if (!sel) return;

            // placeholder mientras carga
            sel.innerHTML = `<option value="">Cargando...</option>`;

            try {
                const res = await fetch("php/api/categorias/list.php", { cache: "no-store" });
                const text = await res.text();

                let j;
                try {
                    j = JSON.parse(text);
                } catch (e) {
                    console.error("categorias/list.php NO regresó JSON:", text);
                    sel.innerHTML = `<option value="">Error (respuesta no JSON)</option>`;
                    return;
                }

                if (!j.ok) {
                    console.error("API categorias respondió ok=false:", j);
                    sel.innerHTML = `<option value="">Error: ${escapeHtml(j.error || "No se pudieron cargar")}</option>`;
                    return;
                }

                CATEGORIAS = Array.isArray(j.data) ? j.data : [];

                sel.innerHTML =
                    `<option value="">-- Sin categoría --</option>` +
                    CATEGORIAS.map(c => `<option value="${c.id}">${escapeHtml(c.nombre)}</option>`).join("");

                sel.value = String(selectedId ?? "");
            } catch (e) {
                console.error("Error cargando categorías:", e);
                sel.innerHTML = `<option value="">Error al cargar</option>`;
            }
        }



        const btnNuevoProducto = document.getElementById("btnNuevoProducto");
        const btnNuevoMov = document.getElementById("btnNuevoMov");
        const btnKardexTodos = document.getElementById("btnKardexTodos");

        // modal producto
        const btnGuardarProducto = document.getElementById("btnGuardarProducto");

        // modal mov
        const movProducto = document.getElementById("movProducto");
        const movTipo = document.getElementById("movTipo");
        const movCantidad = document.getElementById("movCantidad");
        const movRef = document.getElementById("movRef");
        const movNota = document.getElementById("movNota");
        const btnGuardarMov = document.getElementById("btnGuardarMov");

        let PRODUCTOS = [];
        let currentMovFilter = 0;

        const fmtMoney = (n) => `$${Number(n || 0).toFixed(2)}`;

        // =========================
        // Cargar categorías
        // =========================
        async function loadCategorias() {
            try {
                // Cargar categorías (y refrescar el SELECT del modal producto)
                await fillProdCategoriasSelect(document.querySelector("#modalProducto #prodCategoriaId")
                );

                // Actualizar tabla de categorías en el modal
                if (catTbody) {
                    catTbody.innerHTML = CATEGORIAS.length
                        ? CATEGORIAS.map(c => `
                            <tr>
                              <td>
                                <input class="input2" value="${escapeHtml(c.nombre)}" data-cat-name="${c.id}">
                              </td>
                              <td class="num">
                                <div class="row-actions">
                                  <button class="btn-sm" type="button" data-cat-save="${c.id}">Guardar</button>
                                  <button class="btn-sm" type="button" data-cat-del="${c.id}">Eliminar</button>
                                </div>
                              </td>
                            </tr>
                          `).join("")
                        : `<tr><td colspan="2" class="muted">Sin categorías.</td></tr>`;
                }
            } catch (e) {
                console.error("Error cargando categorías:", e);
                toast("Error", String(e.message || e), { icon: "⚠️" });
            }
        }

        // =========================
        // Cargar productos
        // =========================
        async function loadProductos() {
            const q = (invSearch?.value || "").trim();
            const bajo = invSoloBajo?.checked ? 1 : 0;
            const incluirInactivos = invMostrarInactivos?.checked ? 1 : 0;

            invTbody.innerHTML = `<tr><td colspan="8" class="muted">Cargando…</td></tr>`;

            try {
                const res = await fetch(`php/api/productos/list.php?q=${encodeURIComponent(q)}&bajo=${bajo}&incluir_inactivos=${incluirInactivos}`);
                const text = await res.text();

                let j;
                try { j = JSON.parse(text); }
                catch {
                    throw new Error(`Productos: respuesta no es JSON (status ${res.status})`);
                }

                if (!j.ok) throw new Error(j.error || "No se pudieron cargar productos");

                PRODUCTOS = Array.isArray(j.data) ? j.data : [];
                if (invCount) invCount.textContent = `${PRODUCTOS.length} productos`;

                // stock bajo
                const bajoN = PRODUCTOS.filter(p => Number(p.stock_actual) <= Number(p.stock_minimo)).length;
                if (invBajoCount) {
                    invBajoCount.style.display = bajoN > 0 ? "inline-flex" : "none";
                    invBajoCount.textContent = `Stock bajo: ${bajoN}`;
                }

                // llenar select de movimientos
                if (movProducto) {
                    movProducto.innerHTML = `<option value="">-- Elegir --</option>` + PRODUCTOS
                        .map(p => `<option value="${p.id}">${escapeHtml(p.nombre)} (stock ${p.stock_actual})</option>`)
                        .join("");
                }

                if (PRODUCTOS.length === 0) {
                    invTbody.innerHTML = `<tr><td colspan="8" class="muted">Sin resultados.</td></tr>`;
                    return;
                }

                invTbody.innerHTML = PRODUCTOS.map(p => {
                    const bajo = Number(p.stock_actual) <= Number(p.stock_minimo);
                    const activo = (p.activo == 1 || p.activo === "1");
                    return `
                      <tr>
                        <td>
                          <b>${escapeHtml(p.nombre)}</b>
                          <div class="xs muted" style="margin-top:4px;">SKU: ${escapeHtml(p.sku || "-")}</div>
                        </td>
                        <td>${escapeHtml(p.categoria || "-")}</td>
                        <td class="num">${fmtMoney(p.costo)}</td>
                        <td class="num">${fmtMoney(p.precio)}</td>
                        <td class="num"><span class="tag ${bajo ? "tag--bad" : "tag--ok"}">${Number(p.stock_actual)}</span></td>
                        <td class="num">${Number(p.stock_minimo)}</td>
                        <td><span class="tag ${activo ? "tag--ok" : "tag--bad"}">${activo ? "Activo" : "Inactivo"}</span></td>
                        <td class="num">
                          <div class="row-actions">
                            <button class="btn-sm" type="button" data-edit="${p.id}">Editar</button>
                            <button class="btn-sm" type="button" data-mov="${p.id}">Movimiento</button>
                            <button class="btn-sm" type="button" data-kdx="${p.id}">Kardex</button>
                          </div>
                        </td>
                      </tr>
                    `;
                }).join("");

                // Asignar eventos de edición
                invTbody.querySelectorAll("[data-edit]").forEach(b => {
                    b.addEventListener("click", async () => {
                        const id = Number(b.getAttribute("data-edit"));
                        const p = PRODUCTOS.find(x => Number(x.id) === id);
                        await loadCategorias(); // Asegurar que las categorías estén cargadas
                        openProductoModal(p || null);
                    });
                });

                invTbody.querySelectorAll("[data-mov]").forEach(b => {
                    b.addEventListener("click", () => openMovModal(Number(b.getAttribute("data-mov"))));
                });

                invTbody.querySelectorAll("[data-kdx]").forEach(b => {
                    b.addEventListener("click", () => {
                        currentMovFilter = Number(b.getAttribute("data-kdx"));
                        loadMovimientos(currentMovFilter);
                    });
                });

            } catch (e) {
                invTbody.innerHTML = `<tr><td colspan="8" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 4000 });
            }
        }

        // =========================
        // Modal producto (CORREGIDO)
        // =========================
        function openProductoModal(p) {
            modalOpen("modalProducto");

            // Esperar un momento para que el modal esté en el DOM visible
            setTimeout(async () => {
                const prodTitle = document.getElementById("prodTitle");
                const prodId = document.getElementById("prodId");
                const prodSku = document.getElementById("prodSku");
                const prodNombre = document.getElementById("prodNombre");
                const prodCosto = document.getElementById("prodCosto");
                const prodPrecio = document.getElementById("prodPrecio");
                const prodMin = document.getElementById("prodMin");
                const prodStockIni = document.getElementById("prodStockIni");
                const prodActivo = document.getElementById("prodActivo");

                // ✅ Llenar SIEMPRE el select desde la API y seleccionar el valor correcto
                await fillProdCategoriasSelect(p ? (p.categoria_id || "") : "");

                const prodCategoriaId = document.querySelector("#modalProducto #prodCategoriaId")
                    ;
                if (!prodCategoriaId) {
                    console.error("Elemento prodCategoriaId no encontrado en el DOM");
                    toast("Error", "No se puede cargar el formulario", { icon: "⚠️" });
                    return;
                }

                if (!p) {
                    if (prodTitle) prodTitle.textContent = "Nuevo producto";
                    if (prodId) prodId.value = "0";
                    if (prodSku) prodSku.value = "";
                    if (prodNombre) prodNombre.value = "";
                    prodCategoriaId.value = "";
                    if (prodCosto) prodCosto.value = "0";
                    if (prodPrecio) prodPrecio.value = "0";
                    if (prodMin) prodMin.value = "0";
                    if (prodStockIni) { prodStockIni.value = "0"; prodStockIni.disabled = false; }
                    if (prodActivo) prodActivo.value = "1";
                } else {
                    if (prodTitle) prodTitle.textContent = "Editar producto";
                    if (prodId) prodId.value = String(p.id);
                    if (prodSku) prodSku.value = p.sku || "";
                    if (prodNombre) prodNombre.value = p.nombre || "";
                    prodCategoriaId.value = String(p.categoria_id || "");
                    if (prodCosto) prodCosto.value = Number(p.costo || 0);
                    if (prodPrecio) prodPrecio.value = Number(p.precio || 0);
                    if (prodMin) prodMin.value = Number(p.stock_minimo || 0);
                    if (prodStockIni) { prodStockIni.value = "0"; prodStockIni.disabled = true; }
                    if (prodActivo) prodActivo.value = String(Number(p.activo ?? 1));
                }

                prodNombre?.focus();
            }, 50);
        }

        // =========================
        // Guardar producto
        // =========================
        async function saveProducto() {
            const prodIdEl = document.getElementById("prodId");
            const prodSkuEl = document.getElementById("prodSku");
            const prodNombreEl = document.getElementById("prodNombre");
            const prodCategoriaIdEl = document.querySelector("#modalProducto #prodCategoriaId")
                ;
            const prodCostoEl = document.getElementById("prodCosto");
            const prodPrecioEl = document.getElementById("prodPrecio");
            const prodMinEl = document.getElementById("prodMin");
            const prodStockIniEl = document.getElementById("prodStockIni");
            const prodActivoEl = document.getElementById("prodActivo");

            if (!prodNombreEl || !prodCategoriaIdEl) {
                toast("Error", "Faltan campos en el formulario", { icon: "⚠️" });
                return;
            }

            const id = Number(prodIdEl?.value || 0);
            const payload = {
                id,
                sku: (prodSkuEl?.value || "").trim(),
                nombre: (prodNombreEl?.value || "").trim(),
                categoria_id: Number(prodCategoriaIdEl?.value || 0),
                costo: Number(prodCostoEl?.value || 0),
                precio: Number(prodPrecioEl?.value || 0),
                stock_minimo: Number(prodMinEl?.value || 0),
                activo: Number(prodActivoEl?.value || 1),
            };

            if (!payload.nombre) {
                toast("Falta nombre", "Ponle nombre al producto.", { icon: "⚠️" });
                return;
            }

            try {
                if (id === 0) {
                    payload.stock_actual = Number(prodStockIniEl?.value || 0);
                    const res = await fetch("php/api/productos/create.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(payload),
                    });
                    const j = await res.json();
                    if (!j.ok) throw new Error(j.error || "No se pudo crear");
                } else {
                    const res = await fetch("php/api/productos/update.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(payload),
                    });
                    const j = await res.json();
                    if (!j.ok) throw new Error(j.error || "No se pudo actualizar");
                }

                modalClose("modalProducto");
                toast("Listo", "Producto guardado ✅", { icon: "📦" });
                await loadProductos();
                await loadMovimientos(currentMovFilter);

            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        }

        // =========================
        // Modal movimiento
        // =========================
        function openMovModal(productoId) {
            movTipo.value = "ENTRADA";
            movCantidad.value = "1";
            movRef.value = "";
            movNota.value = "";
            if (productoId > 0) movProducto.value = String(productoId);
            modalOpen("modalMov");
        }

        // =========================
        // Guardar movimiento
        // =========================
        async function saveMovimiento() {
            const producto_id = Number(movProducto.value || 0);
            const tipo = String(movTipo.value || "ENTRADA");
            const cantidad = Number(movCantidad.value || 0);
            const referencia = (movRef.value || "").trim();
            const nota = (movNota.value || "").trim();

            if (!producto_id) {
                toast("Falta producto", "Selecciona un producto.", { icon: "⚠️" });
                return;
            }
            if (!cantidad || Number.isNaN(cantidad)) {
                toast("Cantidad inválida", "Pon una cantidad válida.", { icon: "⚠️" });
                return;
            }

            try {
                const res = await fetch("php/api/inventario/mov_create.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ producto_id, tipo, cantidad, referencia, nota }),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo guardar el movimiento");

                modalClose("modalMov");
                toast("Movimiento", "Inventario actualizado ✅", { icon: "✅" });

                await loadProductos();
                await loadMovimientos(producto_id);

            } catch (e) {
                toast("Error", String(e.message || e), { icon: "⚠️", ttl: 3800 });
            }
        }

        // =========================
        // Cargar movimientos
        // =========================
        async function loadMovimientos(productoId) {
            movTbody.innerHTML = `<tr><td colspan="6" class="muted">Cargando…</td></tr>`;
            const url = productoId > 0
                ? `php/api/inventario/mov_list.php?producto_id=${encodeURIComponent(productoId)}`
                : `php/api/inventario/mov_list.php`;

            try {
                const res = await fetch(url);
                const j = await res.json();
                if (!j.ok) {
                    movTbody.innerHTML = `<tr><td colspan="6" class="muted">Error: ${escapeHtml(j.error || '')}</td></tr>`;
                    return;
                }

                const rows = Array.isArray(j.data) ? j.data : [];
                if (rows.length === 0) {
                    movTbody.innerHTML = `<tr><td colspan="6" class="muted">Sin movimientos.</td></tr>`;
                    return;
                }

                movTbody.innerHTML = rows.map(m => {
                    const dt = String(m.created_at || "");
                    const tipo = String(m.tipo || "");
                    const qty = Number(m.cantidad || 0);
                    const tag = qty < 0 ? "tag--bad" : "tag--ok";
                    return `
                      <tr>
                        <td>${escapeHtml(dt)}</td>
                        <td>${escapeHtml(m.producto || "")}</td>
                        <td>${escapeHtml(tipo)}</td>
                        <td class="num"><span class="tag ${tag}">${qty}</span></td>
                        <td>${escapeHtml(m.referencia || "—")}</td>
                        <td>${escapeHtml(m.nota || "—")}</td>
                      </tr>
                    `;
                }).join("");
            } catch (e) {
                movTbody.innerHTML = `<tr><td colspan="6" class="muted">Error: ${escapeHtml(e.message || e)}</td></tr>`;
            }
        }

        // =========================
        // Handlers categorías
        // =========================
        function initCategoriasHandlers() {
            // Abrir modal categorías
            btnCategorias?.addEventListener("click", async () => {
                try {
                    await loadCategorias();
                    modalOpen("modalCategorias");
                    setTimeout(() => catNombre?.focus(), 60);
                } catch (e) {
                    toast("Error", String(e.message || e), { icon: "⚠️" });
                }
            });

            // Crear categoría
            btnCatCrear?.addEventListener("click", async () => {
                const nombre = (catNombre?.value || "").trim();
                if (!nombre) {
                    toast("Falta nombre", "Escribe el nombre de la categoría.", { icon: "⚠️" });
                    return;
                }
                try {
                    const res = await fetch("php/api/categorias/create.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ nombre })
                    });
                    const j = await res.json();
                    if (!j.ok) {
                        toast("Error", j.error || "No se pudo crear", { icon: "⚠️" });
                        return;
                    }
                    if (catNombre) catNombre.value = "";
                    toast("Listo", "Categoría creada ✅", { icon: "🏷️" });

                    await loadCategorias();
                    await loadProductos();
                } catch (e) {
                    toast("Error", String(e.message || e), { icon: "⚠️" });
                }
            });

            // Delegación: Guardar / Eliminar
            catTbody?.addEventListener("click", async (e) => {
                const btnSave = e.target.closest("[data-cat-save]");
                const btnDel = e.target.closest("[data-cat-del]");

                if (btnSave) {
                    const id = Number(btnSave.getAttribute("data-cat-save"));
                    const inp = catTbody.querySelector(`[data-cat-name="${id}"]`);
                    const nombre = (inp?.value || "").trim();
                    if (!nombre) return toast("Error", "Nombre vacío", { icon: "⚠️" });

                    try {
                        const res = await fetch("php/api/categorias/update.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ id, nombre })
                        });
                        const j = await res.json();
                        if (!j.ok) return toast("Error", j.error || "No se pudo actualizar", { icon: "⚠️" });

                        toast("Listo", "Categoría actualizada ✅", { icon: "🏷️" });
                        await loadCategorias();
                        await loadProductos();
                    } catch (e) {
                        toast("Error", String(e.message || e), { icon: "⚠️" });
                    }
                    return;
                }

                if (btnDel) {
                    const id = Number(btnDel.getAttribute("data-cat-del"));
                    if (!confirm("¿Eliminar categoría? (los productos quedarán sin categoría)")) return;

                    try {
                        const res = await fetch("php/api/categorias/delete.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ id })
                        });
                        const j = await res.json();
                        if (!j.ok) return toast("Error", j.error || "No se pudo eliminar", { icon: "⚠️" });

                        toast("Ok", "Categoría eliminada ✅", { icon: "🗑️" });
                        await loadCategorias();
                        await loadProductos();
                    } catch (e) {
                        toast("Error", String(e.message || e), { icon: "⚠️" });
                    }
                }
            });
        }

        // =========================
        // Event Listeners
        // =========================
        invSearch?.addEventListener("input", () => loadProductos());
        invSoloBajo?.addEventListener("change", () => loadProductos());
        invMostrarInactivos?.addEventListener("change", () => loadProductos());

        btnNuevoProducto?.addEventListener("click", async () => {
            await loadCategorias();
            openProductoModal(null);
        });

        btnNuevoMov?.addEventListener("click", () => {
            openMovModal(0);
        });

        btnKardexTodos?.addEventListener("click", () => {
            currentMovFilter = 0;
            loadMovimientos(0);
        });

        btnGuardarProducto?.addEventListener("click", saveProducto);
        btnGuardarMov?.addEventListener("click", saveMovimiento);

        // Si venimos de un link con ?bajo=1 (ej. la tarjeta de Inventario en el
        // dashboard principal), abrimos directo con el filtro de stock bajo activo.
        try {
            const params = new URLSearchParams(location.search);
            if (params.get("bajo") === "1" && invSoloBajo) invSoloBajo.checked = true;
        } catch (e) {}

        // Inicializar
        initCategoriasHandlers();
        loadCategorias()
            .then(() => loadProductos())
            .then(() => loadMovimientos(0))
            .catch(() => loadProductos().then(() => loadMovimientos(0)));
    });

    // =========================
    // Modo alto contraste (toggle en el header de escritorio)
    // =========================
    function initTemaSwitch() {
        const btn = document.getElementById("btnTemaSwitch");
        if (!btn) return; // no estamos en el dashboard de escritorio

        const icon = document.getElementById("temaSwitchIcon");
        const label = document.getElementById("temaSwitchLabel");

        btn.addEventListener("click", async () => {
            const actual = document.documentElement.getAttribute("data-tema") === "alto_contraste" ? "alto_contraste" : "oscuro";
            const nuevo = actual === "alto_contraste" ? "oscuro" : "alto_contraste";

            // Aplicar de inmediato (sin esperar la red) para que se sienta instantáneo.
            document.documentElement.setAttribute("data-tema", nuevo);
            if (icon) icon.textContent = nuevo === "alto_contraste" ? "☀️" : "🌙";
            if (label) label.textContent = nuevo === "alto_contraste" ? "Alto contraste" : "Oscuro";

            try {
                const res = await fetch("php/api/usuarios/tema.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ tema: nuevo }),
                });
                const j = await res.json();
                if (!j.ok) throw new Error(j.error || "No se pudo guardar la preferencia");
            } catch (e) {
                // Si falla el guardado, dejamos el cambio visual (ya aplicado) pero avisamos
                // de que no quedó guardado para la próxima sesión.
                toast("Aviso", "El tema cambió, pero no se pudo guardar tu preferencia.", { icon: "⚠️", ttl: 3500 });
            }
        });
    }

    // =========================
    // Boot
    // =========================
    window.addEventListener("DOMContentLoaded", () => {
        initVentaModalUI();
        initVentasTable();
        initCobrarHandler();
        initServiciosCRUD();
        initTemaSwitch();
    });

    // Nota: el bloque legacy "AUTOLAVADO" que vivía aquí (loadAutolavado,
    // crearAuto, cambiarEstado, cobrarAuto) se quitó: apuntaba a IDs
    // (autoTbody, autoServicio, modalAuto) que ya no existen en
    // pages/autolavado.php, así que nunca hacía nada útil y solo generaba
    // 2 llamadas de red de más en cada carga de página del sitio. La
    // lógica real de autolavado vive en el <script> propio de
    // pages/autolavado.php.

})();