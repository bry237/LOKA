document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('searchLocataire');

    const cards =
        document.querySelectorAll('.locataire-card');

    const noSearchResult =
        document.getElementById('noSearchResult');


    if (!searchInput) {
        return;
    }


    function rechercherLocataire() {

        const recherche =
            searchInput.value
                .toLowerCase()
                .trim();


        let nombreVisible = 0;


        cards.forEach(function (card) {

            const contenu =
                (
                    card.dataset.search || ''
                ).toLowerCase();


            const visible =
                recherche === ''
                ||
                contenu.includes(recherche);


            card.style.display =
                visible ? '' : 'none';


            if (visible) {
                nombreVisible++;
            }

        });


        if (noSearchResult) {

            noSearchResult.hidden =
                nombreVisible !== 0;

        }

    }


    searchInput.addEventListener(
        'input',
        rechercherLocataire
    );


    searchInput.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                searchInput.value = '';

                rechercherLocataire();

                searchInput.blur();

            }

        }
    );

});