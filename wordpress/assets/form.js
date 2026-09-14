(() => {
  const forms = document.querySelectorAll("[data-lumail-form]");
  if (!forms.length) return;

  const ajaxUrl =
    (window.lumailForm && window.lumailForm.ajaxUrl) ||
    "/wp-admin/admin-ajax.php";
  const fallbackSuccess =
    (window.lumailForm && window.lumailForm.success) ||
    "Check your inbox to confirm.";

  forms.forEach((form) => {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const button = form.querySelector("[type=submit]");
      const message = form.querySelector(".lumail-form__message");
      if (!button || !message) return;

      button.disabled = true;
      message.hidden = true;
      message.classList.remove("is-error");

      try {
        const response = await fetch(ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: new URLSearchParams(new FormData(form)),
        });
        const payload = await response.json().catch(() => ({}));
        const ok = Boolean(payload.success);
        const text =
          (payload.data && payload.data.message) ||
          (ok ? fallbackSuccess : "Could not subscribe right now.");
        message.textContent = text;
        message.classList.toggle("is-error", !ok);
        message.hidden = false;
        if (ok) form.reset();
      } catch {
        message.textContent = "Could not subscribe right now.";
        message.classList.add("is-error");
        message.hidden = false;
      } finally {
        button.disabled = false;
      }
    });
  });
})();
