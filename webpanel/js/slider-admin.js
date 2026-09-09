(function () {
    var list = document.getElementById('slider-list');
    var dropzone = document.getElementById('slider-dropzone');
    var fileInput = document.getElementById('slide_images');
    var namesEl = document.getElementById('slider-drop-names');
    var createForm = document.getElementById('slider-create-form');

    function showNames(files) {
        if (!namesEl) return;
        if (!files || !files.length) {
            namesEl.classList.add('hidden');
            namesEl.textContent = '';
            return;
        }
        var names = [];
        for (var i = 0; i < files.length; i++) {
            names.push(files[i].name);
        }
        namesEl.textContent = names.join(', ');
        namesEl.classList.remove('hidden');
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            showNames(fileInput.files);
        });
    }

    if (dropzone && fileInput) {
        ['dragenter', 'dragover'].forEach(function (type) {
            dropzone.addEventListener(type, function (ev) {
                ev.preventDefault();
                dropzone.classList.add('border-[#2271b1]', 'bg-blue-50');
            });
        });
        ['dragleave', 'drop'].forEach(function (type) {
            dropzone.addEventListener(type, function (ev) {
                ev.preventDefault();
                dropzone.classList.remove('border-[#2271b1]', 'bg-blue-50');
            });
        });
        dropzone.addEventListener('drop', function (ev) {
            ev.preventDefault();
            var files = ev.dataTransfer && ev.dataTransfer.files ? ev.dataTransfer.files : null;
            if (!files || !files.length) return;
            if (typeof DataTransfer !== 'undefined') {
                try {
                    var dt = new DataTransfer();
                    for (var i = 0; i < files.length; i++) {
                        dt.items.add(files[i]);
                    }
                    fileInput.files = dt.files;
                } catch (err) {}
            }
            showNames(files);
            if (createForm && files.length && createForm.getAttribute('data-preview') !== '1') {
                createForm.submit();
            }
        });
    }

    if (!list) return;

    var dragItem = null;

    function idsInOrder() {
        var ids = [];
        list.querySelectorAll('.slider-item').forEach(function (item) {
            ids.push(item.getAttribute('data-id'));
        });
        return ids;
    }

    function saveOrder() {
        if (list.getAttribute('data-preview') === '1') return;
        var tokenInput = list.querySelector('input[name="csrf_token"]');
        var token = list.getAttribute('data-csrf') || (tokenInput ? tokenInput.value : '');
        var body = new FormData();
        body.append('csrf_token', token);
        body.append('action', 'reorder');
        body.append('ajax', '1');
        idsInOrder().forEach(function (id) {
            body.append('order[]', id);
        });
        fetch('index.php?page=slider', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false }; });
        }).then(function (data) {
            if (!data || !data.ok) {
                window.location.reload();
            }
        }).catch(function () {
            window.location.reload();
        });
    }

    list.querySelectorAll('.slider-item').forEach(function (item) {
        var handle = item.querySelector('[data-drag-handle]');
        if (handle) {
            handle.addEventListener('mousedown', function () {
                item.setAttribute('draggable', 'true');
            });
            handle.addEventListener('mouseup', function () {
                item.setAttribute('draggable', 'false');
            });
        }
        item.addEventListener('dragstart', function (ev) {
            dragItem = item;
            item.classList.add('opacity-60', 'ring-2', 'ring-[#2271b1]');
            if (ev.dataTransfer) {
                ev.dataTransfer.effectAllowed = 'move';
                ev.dataTransfer.setData('text/plain', item.getAttribute('data-id') || '');
            }
        });
        item.addEventListener('dragend', function () {
            item.classList.remove('opacity-60', 'ring-2', 'ring-[#2271b1]');
            item.setAttribute('draggable', 'false');
            list.querySelectorAll('.slider-item').forEach(function (el) {
                el.classList.remove('border-[#2271b1]');
            });
            dragItem = null;
        });
        item.addEventListener('dragover', function (ev) {
            ev.preventDefault();
            if (!dragItem || dragItem === item) return;
            var rect = item.getBoundingClientRect();
            var before = (ev.clientY - rect.top) < rect.height / 2;
            if (before) {
                list.insertBefore(dragItem, item);
            } else {
                list.insertBefore(dragItem, item.nextSibling);
            }
        });
        item.addEventListener('drop', function (ev) {
            ev.preventDefault();
        });
    });

    list.addEventListener('dragend', function () {
        if (dragItem) return;
        saveOrder();
    });
})();
