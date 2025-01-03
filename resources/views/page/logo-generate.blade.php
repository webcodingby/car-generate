 @extends('app')
 @section('title', 'Генератор логотипов для автосалонов')
 @section('content')
    <form id="logoForm" action="" method="POST" enctype="multipart/form-data">
        @csrf
        <!-- Поля формы -->
        <div>
            <label for="name">Название:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
            <label for="icon">Иконка:</label>
            <div class="icons__pack" style="display: flex; flex-wrap: wrap; gap: 10px;">
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
        .icons__pack .icon-preview:hover {
            border-color: #007bff;
        }
        .icons__pack input[name="icon"]:checked + .icon-preview {
            border-color: #007bff !important;
        }
        .logo__footer{
            background: #000;
        }
        #generatedLogos{
            max-width: 200px;
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
                .then(response => {
                    console.log('Ответ сервера:', response);
                    return response.json();
                })
                .then(data => {
                    if (data && data.data) {
                        console.log('Данные успешно получены:', data.data);
                        renderLogoBloks(data.data);
                    } else {
                        console.error('Некорректный формат данных:', data);
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        });
        function renderLogoBloks(data) {
            const timestamp = new Date().getTime();
            document.getElementById('generatedLogos').style.display = 'block';
            document.getElementById('logo').innerHTML = `<h3>Логотип:</h3><div><img src='${data.logo}?t=${timestamp}' alt='Logo'></div>`;
            document.getElementById('logoFooter').innerHTML = `<h3>Инвертированный логотип:</h3><div class="logo__footer"><img src='${data.logo_footer}?t=${timestamp}' alt='Logo Footer'></div>`;
            document.getElementById('faviconContainer').innerHTML = `<h3>Фавикон Цветной:</h3><img src='${data.favicon_colored}?t=${timestamp}' alt='Favicon' width='16' height='16'>`;
        }

    </script>
@endsection
