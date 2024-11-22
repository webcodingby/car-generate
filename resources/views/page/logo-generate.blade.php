
<form id="logoForm" action="" method="POST" enctype="multipart/form-data">
    @csrf
    <!-- Поля формы -->
    <div>
        <label for="name">Название:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
        <label for="icon">Иконка:</label>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach ($icons as $icon)
                <label style="display: inline-block; text-align: center; cursor: pointer;">
                    <input
                        type="radio"
                        name="icon"
                        value="{{ $icon }}"
                        style="display: none;"
                        @if(old('icon') == $icon) checked @endif
                    >
                    <img
                        src="{{ asset('storage/' . $icon) }}"
                        alt="icon"
                        style="width: 50px; height: 50px; border: 2px solid transparent; border-radius: 5px;"
                        class="icon-preview"
                    >
                </label>
            @endforeach
        </div>
    </div>
    <div>
        <label for="font">Шрифт:</label>
        <select id="font" name="font">
            <!-- Опции для выбора шрифта -->
            @foreach ($fonts as $font)
                <option value="{{ $font }}">{{ $font }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="color1">Цвет 1:</label>
        <input type="color" id="color1" name="color1" required>
    </div>
    <div>
        <label for="color2">Цвет 2 (опционально):</label>
        <input type="color" id="color2" name="color2">
    </div>
    <button type="button" id="generateButton">Сгенерировать</button>
</form>
<!-- Блок для отображения сгенерированных изображений -->
<div id="generatedLogos" class="mt-4" style="display: none;">
    <h2>Сгенерированные логотипы:</h2>
    <div id="logo"></div>
    <div id="logoFooter"></div>
    <div id="faviconContainer"></div>
</div>
<style>
    /* Стили для выделенной иконки */
    .icon-preview:hover {
        border-color: #007bff;
    }
    input[name="icon"]:checked + .icon-preview {
        border-color: #007bff;
    }
</style>
<script>
    document.getElementById('color1').value = '';
    document.getElementById('color2').value = '';
    document.getElementById('generateButton').addEventListener('click', function () {
        // Отправка формы с помощью AJAX
        const formData = new FormData(document.getElementById('logoForm'));
        fetch('/api/logo-save', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                console.log(data.data);
                // Отображение сгенерированных изображений
                document.getElementById('generatedLogos').style.display = 'block';
                document.getElementById('logo').innerHTML = `<h3>Логотип:</h3><div>${data.data.logo}</div>`;
                document.getElementById('logoFooter').innerHTML = `<h3>Инвертированный логотип:</h3><div>${data.logo_footer}</div>`;
                document.getElementById('faviconContainer').innerHTML = `<h3>Фавикон:</h3><img src='${data.data.favicon}' alt='Favicon' width='16' height='16'>`;
            })
            .catch(error => console.error('Ошибка:', error));
    });
</script>
