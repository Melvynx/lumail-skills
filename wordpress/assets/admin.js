(() => {
  const button = document.getElementById("lumail-ping");
  const result = document.getElementById("lumail-ping-result");
  if (!button || !result || !window.lumailAdmin) return;

  button.addEventListener("click", async () => {
    button.disabled = true;
    result.textContent = "Checking…";
    result.className = "";
    try {
      const body = new URLSearchParams({
        action: "lumail_ping",
        nonce: window.lumailAdmin.nonce,
      });
      const response = await fetch(window.lumailAdmin.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body,
      });
      const payload = await response.json().catch(() => ({}));
      const ok = Boolean(payload.success);
      result.textContent =
        (payload.data && payload.data.message) ||
        (ok ? "Connected." : "Connection failed.");
      result.className = ok ? "is-ok" : "is-error";
    } catch {
      result.textContent = "Connection failed.";
      result.className = "is-error";
    } finally {
      button.disabled = false;
    }
  });
})();
