<dialog id="modal-logout" aria-labelledby="modal-logout-titolo"
        class="m-auto p-6 w-[calc(100%-2rem)] max-w-sm border border-slate-200 rounded-xl bg-white shadow-lg backdrop:bg-slate-900/50">
    <h2 id="modal-logout-titolo" class="text-lg font-semibold text-slate-800 mb-2">Conferma uscita</h2>
    <p class="text-sm text-slate-600 mb-6">Sei sicuro di voler uscire da Conti Famiglia?</p>
    <div class="flex justify-end gap-3">
        <button type="button" id="btn-annulla-logout"
            class="px-4 py-2 rounded-lg text-slate-700 font-medium hover:bg-slate-100 transition">
            Annulla
        </button>
        <a href="logout.php" id="conferma-logout"
            class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition">
            Esci
        </a>
    </div>
</dialog>

<script>
    (function () {
        const linkLogout = document.getElementById('link-logout');
        const modale = document.getElementById('modal-logout');
        const btnAnnulla = document.getElementById('btn-annulla-logout');

        linkLogout.addEventListener('click', function (evento) {
            evento.preventDefault();
            modale.showModal();
            btnAnnulla.focus();
        });

        btnAnnulla.addEventListener('click', function () {
            modale.close();
        });

        // Click sul backdrop: l'evento ha come target il dialog stesso
        modale.addEventListener('click', function (evento) {
            if (evento.target === modale) {
                modale.close();
            }
        });

        // Esc è gestito dal browser; il focus torna da solo all'elemento che ha aperto il dialog
    })();
</script>
