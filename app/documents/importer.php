<?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Importer</button>
                        <a href="/LOKA/app/documents/index.php" class="btn btn-outline">Annuler</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
function updateEntityDropdown() {
    // Hide all entity selects and clear their values
    document.querySelectorAll('.entity-select').forEach(function(el) {
        el.style.display = 'none';
        var select = el.querySelector('select');
        if (select) select.value = '';
    });
    
    // Show selected entity
    var type = document.getElementById('entity_type').value;
    if (type) {
        var group = document.getElementById('group_' + type);
        if (group) group.style.display = 'block';
    }
}
</script>
</body>
</html>