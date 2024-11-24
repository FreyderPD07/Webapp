document.addEventListener('DOMContentLoaded', () => {
    const municipioInput = document.getElementById('municipio');
    const departamentoSelect = document.getElementById('departamento');
    const ciudadIdInput = document.getElementById('ciudad_id');
    const suggestionsBox = document.getElementById('municipio-suggestions');

    municipioInput.addEventListener('input', () => {
        const query = municipioInput.value.trim();
        const departamentoId = departamentoSelect.value;

        if (query.length > 2 && departamentoId) {
            fetch(`../api/municipios.php?search=${query}&departamento_id=${departamentoId}`)
                .then(response => {
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.success && data.municipios.length > 0) {
                        data.municipios.forEach(municipio => {
                            const suggestion = document.createElement('div');
                            suggestion.textContent = municipio.Nombre_Ciudad;
                            suggestion.classList.add('suggestion-item');
                            suggestion.addEventListener('click', () => {
                                municipioInput.value = municipio.Nombre_Ciudad;
                                ciudadIdInput.value = municipio.CiudadID;
                                suggestionsBox.innerHTML = '';
                            });
                            suggestionsBox.appendChild(suggestion);
                        });
                    } else {
                        ciudadIdInput.value = '';
                        suggestionsBox.innerHTML = '<div class="no-results">No se encontraron municipios. Será añadido automáticamente.</div>';
                    }
                })
                .catch(error => console.error('Error al buscar municipios:', error));
        }
    });

    municipioInput.addEventListener('blur', () => {
        // Verifica si el campo está vacío o si el usuario ingresó un municipio no existente
        if (!ciudadIdInput.value) {
            suggestionsBox.innerHTML = '<div class="no-results">La ciudad se añadirá al enviar.</div>';
        }
    });
});
