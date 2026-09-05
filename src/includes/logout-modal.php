<div id="modal-logout" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center px-4 z-50">
    <dialog id="dialog-logout" open class="bg-white rounded-xl shadow-lg border border-slate-200 p-6 w-full max-w-sm" aria-labelledby="modal-logout-titolo">
        <h2 id="modal-logout-titolo" class="text-lg font-semibold text-slate-800 mb-2">Conferma uscita</h2>
        <p class="text-sm text-slate-600 mb-6">Sei sicuro di voler uscire da Conti Famiglia?</p>
        <div class="flex justify-end gap-3">
            <button type="button" id="btn-annulla-logout"
                class="px-4 py-2 rounded-lg text-slate-700 font-medium hover:bg-slate-100 transition">
                Annulla
            </button>
            <a href="logout.php" id="conferma-logout" class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition">
                Esci
            </a>
        </div>
    </dialog>
</div>

<script>
    (function () {
        const linkLogout = document.getElementById('link-logout');
        const modale = document.getElementById('modal-logout');
        const btnAnnulla = document.getElementById('btn-annulla-logout');

        function apriModale(evento) {
            evento.preventDefault();
            modale.classList.remove('hidden');
            btnAnnulla.focus();
        }

        function chiudiModale() {
            modale.classList.add('hidden');
            linkLogout.focus();
        }

        linkLogout.addEventListener('click', apriModale);
        btnAnnulla.addEventListener('click', chiudiModale);

        modale.addEventListener('click', function (evento) {
            if (evento.target === modale) {
                chiudiModale();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && !modale.classList.contains('hidden')) {
                chiudiModale();
            }
        });
    })();
</script>
