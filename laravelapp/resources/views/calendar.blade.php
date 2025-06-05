<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title id="pageTitle">Calendario Social con Categorías Dinámicas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://unpkg.com/@ffmpeg/ffmpeg@0.11.6/dist/ffmpeg.min.js"></script>
  <style>
    .grid-day { min-height: 120px; }
    .tag { padding: 0.125rem 0.5rem; font-size: 0.75rem; border-radius: 9999px; color: white; display: inline-block; cursor: pointer; }
    .edit-thumb { width: 4rem; }
    .instagram-card { max-width: 350px; border: 1px solid #dbdbdb; background: white; font-family: sans-serif; }
    .instagram-header { display: flex; align-items: center; padding: 4px; }
    .instagram-avatar { width: 24px; height: 24px; background: #ccc; border-radius: 50%; margin-right: 8px; }
    .instagram-icons { display: flex; gap: 8px; padding: 4px; font-size: 1.25rem; }
    .instagram-caption { padding: 4px; font-size: 0.875rem; }
    .facebook-card, .linkedin-card { max-width: 350px; border: 1px solid #dadde1; background: white; font-family: sans-serif; }
    .facebook-header, .linkedin-header { padding: 4px; font-size: 0.75rem; font-weight: bold; color: #1877f2; }
    .facebook-caption, .linkedin-caption { padding: 4px; font-size: 0.875rem; }
    .tiktok-card { max-width: 350px; background: black; color: white; font-family: sans-serif; }
    .tiktok-header { padding: 4px; font-size: 0.75rem; font-weight: bold; }
    .tiktok-caption { padding: 4px; font-size: 0.875rem; }
    .youtube-card { max-width: 350px; border: 1px solid #ddd; background: white; font-family: sans-serif; }
    .youtube-header { padding: 4px; font-size: 0.75rem; font-weight: bold; color: #ff0000; }
    .youtube-caption { padding: 4px; font-size: 0.875rem; }
  </style>
</head>
<body class="bg-gray-100 p-4">
  <div class="max-w-6xl mx-auto">
    <div class="mb-2">
      <input id="calendarName" class="text-xl font-bold bg-transparent border-b" value="Calendario Social" />
      <div class="flex items-center gap-1 mt-2">
        <button onclick="changeMonth(-1)" class="px-2">◀</button>
        <input type="month" id="monthPicker" class="border p-1 rounded" value="2025-06">
        <button onclick="changeMonth(1)" class="px-2">▶</button>
        <button onclick="goToday()" class="px-2" title="Ir al mes actual">Hoy</button>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
      <div class="flex items-center gap-2 text-sm">
        <div id="categoryLegend" class="flex flex-wrap gap-2"></div>
        <button onclick="openCategoryModal()" title="Editar categorías">✏️</button>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <div id="hashtagLegend" class="flex flex-wrap gap-2"></div>
        <button onclick="openHashtagModal()" title="Editar hashtags">✏️</button>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <div id="formatLegend" class="flex flex-wrap gap-2"></div>
        <button onclick="openFormatModal()" title="Editar formatos">✏️</button>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <div id="personLegend" class="flex flex-wrap gap-2"></div>
        <button onclick="openPersonModal()" title="Editar personas">✏️</button>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <div id="networkLegend" class="flex flex-wrap gap-2"></div>
        <button onclick="openNetworkModal()" title="Editar redes">✏️</button>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <button onclick="openDataModal()" class="bg-blue-500 text-white px-2 py-1 rounded text-xs" title="Importar/Exportar">⇅</button>
        <button onclick="clearMonth()" class="bg-red-500 text-white px-2 py-1 rounded text-xs" title="Limpiar Mes">🗑️</button>
        <button onclick="clearAll()" class="bg-red-700 text-white px-2 py-1 rounded text-xs" title="Limpiar Todo">🗑️🗑️</button>
      </div>
    </div>

    <div class="grid grid-cols-7 gap-2 bg-white p-4 rounded shadow" id="calendar"></div>
  </div>

  <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Editar Día <span id="modal-day"></span></h2>
        <details id="previewDetails" open class="mb-2">
          <summary class="cursor-pointer bg-gray-200 px-2 py-1 rounded mb-2">Previsualización</summary>
          <div id="editPreview" class="mt-2"></div>
        </details>
        <div id="editThumb" class="mb-2 hidden edit-thumb mx-auto"></div>
        <details open class="mb-2">
          <summary class="cursor-pointer bg-gray-200 px-2 py-1 rounded mb-2">Principales</summary>
          <div class="mt-2 space-y-2">
            <div class="flex gap-1"><select id="modal-type" class="w-full border p-2 rounded"></select><button type="button" onclick="copyField('modal-type')">📋</button><button type="button" onclick="pasteField('modal-type')">📥</button></div>
            <div class="flex gap-1"><input id="modal-content" class="w-full border p-2 rounded" placeholder="Contenido" oninput="renderEditPreview()"><button type="button" onclick="copyField('modal-content')">📋</button><button type="button" onclick="pasteField('modal-content')">📥</button></div>
            <div class="flex gap-1"><input id="modal-time" type="time" class="w-full border p-2 rounded" placeholder="Hora"><button type="button" onclick="copyField('modal-time')">📋</button><button type="button" onclick="pasteField('modal-time')">📥</button></div>
            <div class="flex gap-1"><select id="modal-format" class="w-full border p-2 rounded"></select><button type="button" onclick="copyField('modal-format')">📋</button><button type="button" onclick="pasteField('modal-format')">📥</button></div>
            <div class="flex gap-1"><select id="modal-network" class="w-full border p-2 rounded"></select><button type="button" onclick="copyField('modal-network')">📋</button><button type="button" onclick="pasteField('modal-network')">📥</button></div>
            <div class="flex gap-1"><textarea id="modal-copy" class="w-full border p-2 rounded" placeholder="Copy" oninput="renderEditPreview()"></textarea><button type="button" onclick="copyField('modal-copy')">📋</button><button type="button" onclick="pasteField('modal-copy')">📥</button></div>
            <div class="flex gap-1"><input id="modal-hashtag-input" class="w-full border p-2 rounded" placeholder="Escribe hashtags y sepáralos con coma" onkeyup="handleHashtagInput(event)"><button type="button" onclick="copyField('modal-hashtag-input')">📋</button><button type="button" onclick="pasteField('modal-hashtag-input')">📥</button></div>
            <div class="flex gap-1"><select id="modal-hashtags" multiple class="w-full border p-2 rounded"></select><button type="button" onclick="copyField('modal-hashtags')">📋</button><button type="button" onclick="pasteField('modal-hashtags')">📥</button></div>
          </div>
        </details>
        <details class="mb-2">
          <summary class="cursor-pointer bg-gray-200 px-2 py-1 rounded mb-2">Medios</summary>
          <div class="mt-2 space-y-2">
            <input id="modal-image-file" type="file" accept="image/*" class="w-full border p-2 rounded" onchange="handleImageFile(event)">
            <label class="flex items-center gap-2"><input type="checkbox" id="modal-carousel" onchange="toggleCarousel()"> Carrusel</label>
            <div class="flex gap-1"><input id="modal-image-url" class="w-full border p-2 rounded" placeholder="URL de imagen" oninput="handleImageUrl(event)"><button type="button" onclick="copyField('modal-image-url')">📋</button><button type="button" onclick="pasteField('modal-image-url')">📥</button></div>
            <div id="editImageWrapper" class="relative w-full aspect-square hidden">
              <img id="modal-image-preview" class="object-cover w-full h-full rounded" />
              <button id="prevImgBtn" type="button" onclick="changeEditImage(-1)" class="absolute left-0 top-0 px-1 bg-white/50 hidden">◀</button>
              <button id="nextImgBtn" type="button" onclick="changeEditImage(1)" class="absolute right-0 top-0 px-1 bg-white/50 hidden">▶</button>
            </div>
            <input id="modal-video-file" type="file" accept="video/*" class="w-full border p-2 rounded" onchange="handleVideoFile(event)">
            <div class="flex gap-1"><input id="modal-video-url" class="w-full border p-2 rounded" placeholder="URL de video" oninput="handleVideoUrl(event)"><button type="button" onclick="copyField('modal-video-url')">📋</button><button type="button" onclick="pasteField('modal-video-url')">📥</button></div>
            <img id="modal-video-preview" class="max-h-24 hidden" />
          </div>
        </details>
        <details class="mb-2">
          <summary class="cursor-pointer bg-gray-200 px-2 py-1 rounded mb-2">Secundarias</summary>
          <div class="mt-2 space-y-2">
            <div class="flex gap-1"><textarea id="modal-copystories" class="w-full border p-2 rounded" placeholder="Copy Stories"></textarea><button type="button" onclick="copyField('modal-copystories')">📋</button><button type="button" onclick="pasteField('modal-copystories')">📥</button></div>
            <div class="flex gap-1"><input id="modal-theme" class="w-full border p-2 rounded" placeholder="Temática"><button type="button" onclick="copyField('modal-theme')">📋</button><button type="button" onclick="pasteField('modal-theme')">📥</button></div>
            <div class="flex gap-1"><input id="modal-visual" class="w-full border p-2 rounded" placeholder="Contenido Visual"><button type="button" onclick="copyField('modal-visual')">📋</button><button type="button" onclick="pasteField('modal-visual')">📥</button></div>
            <div class="flex gap-1"><input id="modal-source" class="w-full border p-2 rounded" placeholder="Fuente"><button type="button" onclick="copyField('modal-source')">📋</button><button type="button" onclick="pasteField('modal-source')">📥</button></div>
            <div class="flex gap-1"><input id="modal-objective" class="w-full border p-2 rounded" placeholder="Objetivo"><button type="button" onclick="copyField('modal-objective')">📋</button><button type="button" onclick="pasteField('modal-objective')">📥</button></div>
            <div class="flex gap-1"><input id="modal-person-input" class="w-full border p-2 rounded" placeholder="Escribe personas y sepáralas con coma" onkeyup="handlePersonInput(event)"><button type="button" onclick="copyField('modal-person-input')">📋</button><button type="button" onclick="pasteField('modal-person-input')">📥</button></div>
            <div class="flex gap-1"><select id="modal-persons" multiple class="w-full border p-2 rounded"></select><button type="button" onclick="copyField('modal-persons')">📋</button><button type="button" onclick="pasteField('modal-persons')">📥</button></div>
          </div>
        </details>
        <div class="flex justify-end gap-2">
          <button onclick="openDuplicateModal()" class="bg-purple-500 text-white px-4 py-2 rounded">Duplicar</button>
          <button onclick="closeEditModal()" class="bg-gray-300 px-4 py-2 rounded">Cancelar</button>
          <button onclick="saveChanges()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        </div>
      </div>
    </div>

  <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">Categorías</h2>
      <ul id="categoryList" class="mb-4 space-y-2"></ul>
      <input id="newCategoryName" class="w-full border mb-2 p-2 rounded" placeholder="Nombre de categoría">
      <input id="newCategoryColor" type="color" class="w-full border mb-2 p-2 rounded">
      <div class="flex justify-end gap-2">
        <button onclick="saveCategoryEdits()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        <button onclick="addCategory()" class="bg-green-600 text-white px-4 py-2 rounded">Agregar</button>
        <button onclick="closeCategoryModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>
  <div id="hashtagModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">Hashtags</h2>
      <ul id="hashtagList" class="mb-4 space-y-2"></ul>
      <input id="newHashtag" class="w-full border mb-2 p-2 rounded" placeholder="Nuevo hashtag">
      <input id="newHashtagColor" type="color" class="w-full border mb-2 p-2 rounded">
      <div class="flex justify-end gap-2">
        <button onclick="saveHashtagEdits()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        <button onclick="addHashtag()" class="bg-green-600 text-white px-4 py-2 rounded">Agregar</button>
        <button onclick="closeHashtagModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>
  <div id="formatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">Formatos</h2>
      <ul id="formatList" class="mb-4 space-y-2"></ul>
      <input id="newFormatName" class="w-full border mb-2 p-2 rounded" placeholder="Nombre de formato">
      <input id="newFormatColor" type="color" class="w-full border mb-2 p-2 rounded">
      <div class="flex justify-end gap-2">
        <button onclick="saveFormatEdits()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        <button onclick="addFormat()" class="bg-green-600 text-white px-4 py-2 rounded">Agregar</button>
        <button onclick="closeFormatModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>
  <div id="personModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">Personas</h2>
      <ul id="personList" class="mb-4 space-y-2"></ul>
      <input id="newPersonName" class="w-full border mb-2 p-2 rounded" placeholder="Nombre">
      <input id="newPersonColor" type="color" class="w-full border mb-2 p-2 rounded">
      <div class="flex justify-end gap-2">
        <button onclick="savePersonEdits()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        <button onclick="addPerson()" class="bg-green-600 text-white px-4 py-2 rounded">Agregar</button>
        <button onclick="closePersonModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>

  <div id="networkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">Redes</h2>
      <ul id="networkList" class="mb-4 space-y-2"></ul>
      <input id="newNetworkName" class="w-full border mb-2 p-2 rounded" placeholder="Nombre">
      <input id="newNetworkIcon" class="w-full border mb-2 p-2 rounded" placeholder="Icono">
      <input id="newNetworkColor" type="color" class="w-full border mb-2 p-2 rounded">
      <div class="flex justify-end gap-2">
        <button onclick="saveNetworkEdits()" class="bg-blue-500 text-white px-4 py-2 rounded">Guardar</button>
        <button onclick="addNetwork()" class="bg-green-600 text-white px-4 py-2 rounded">Agregar</button>
        <button onclick="closeNetworkModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>

  <div id="duplicateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-xs w-full">
      <h2 class="text-xl font-bold mb-4">Duplicar Día</h2>
      <input type="date" id="duplicate-date" class="w-full border mb-2 p-2 rounded">
      <div id="duplicateFields" class="space-y-1 mb-2"></div>
      <div class="flex justify-end gap-2">
        <button onclick="closeDuplicateModal()" class="bg-gray-300 px-4 py-2 rounded">Cancelar</button>
        <button onclick="confirmDuplicate()" class="bg-blue-500 text-white px-4 py-2 rounded">Copiar</button>
      </div>
    </div>
  </div>

  <div id="dataModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow max-w-md w-full space-y-4">
      <h2 class="text-xl font-bold mb-2">Importar / Exportar</h2>
        <input type="date" id="rangeStart" class="w-full border mb-2 p-2 rounded">
        <input type="date" id="rangeEnd" class="w-full border mb-2 p-2 rounded">
      <details class="border rounded p-2">
        <summary class="cursor-pointer">Campos a importar / exportar</summary>
        <div id="fieldOptions" class="space-y-1 mt-2"></div>
      </details>
      <div class="space-y-2 text-sm">
        <button onclick="exportData('csv')" class="bg-amber-500 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar CSV">⬇️ CSV</button>
        <button onclick="exportData('excel')" class="bg-green-500 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar Excel">⬇️ XLSX</button>
        <button onclick="exportPDF()" class="bg-gray-700 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar PDF">⬇️ PDF</button>
        <button onclick="exportImage('png')" class="bg-teal-500 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar PNG">🖼️ PNG</button>
        <button onclick="exportImage('jpg')" class="bg-pink-500 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar JPG">🖼️ JPG</button>
        <button onclick="exportImage('webp')" class="bg-orange-500 text-white px-2 py-1 rounded w-full flex items-center justify-center gap-1" title="Exportar WEBP">🖼️ WEBP</button>
        <label class="bg-blue-500 text-white px-2 py-1 rounded cursor-pointer w-full text-center block" title="Importar CSV">
          ⬆️ CSV<input type="file" accept=".csv" onchange="importCSV(event)" class="hidden">
        </label>
        <label class="bg-indigo-500 text-white px-2 py-1 rounded cursor-pointer w-full text-center block" title="Importar Imagenes CSV">
          ⬆️ Img<input type="file" accept=".csv" onchange="importImagesCSV(event)" class="hidden">
        </label>
        <label class="bg-indigo-700 text-white px-2 py-1 rounded cursor-pointer w-full text-center block" title="Importar Videos CSV">
          ⬆️ Vid<input type="file" accept=".csv" onchange="importVideosCSV(event)" class="hidden">
        </label>
        <label class="bg-purple-500 text-white px-2 py-1 rounded cursor-pointer w-full text-center block" title="Importar Excel">
          ⬆️ XLSX<input type="file" accept=".xlsx" onchange="importExcel(event)" class="hidden">
        </label>
        <input id="csvUrl" class="w-full border p-2 rounded" placeholder="URL CSV">
        <button onclick="importCSVFromUrl()" class="bg-blue-500 text-white px-2 py-1 rounded w-full" title="Importar desde URL">⬆️ URL</button>
        <textarea id="csvText" class="w-full border p-2 rounded" placeholder="Pega CSV aquí"></textarea>
        <button onclick="importCSVFromText()" class="bg-blue-500 text-white px-2 py-1 rounded w-full" title="Importar texto CSV">⬆️ Texto</button>
      </div>
      <div class="flex justify-end">
        <button onclick="closeDataModal()" class="bg-gray-300 px-4 py-2 rounded">Cerrar</button>
      </div>
    </div>
  </div>

  <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-4 rounded shadow max-w-md w-full">
      <div class="flex justify-between items-center mb-2">
        <h3 id="previewDate" class="font-bold"></h3>
        <button onclick="closePreviewModal()">✖</button>
      </div>
      <div id="previewMedia" class="mb-2"></div>
      <div id="previewInfo" class="text-sm space-y-1"></div>
      <div class="flex justify-end mt-2">
        <button onclick="openEditModal(selectedDate.key, selectedDate.day)" class="bg-blue-500 text-white px-3 py-1 rounded">Editar</button>
      </div>
    </div>
  </div>

  <div id="tooltip" class="absolute bg-white p-2 rounded shadow text-xs border hidden z-10 w-64 pointer-events-none"></div>

  <script>
    let calendarData = JSON.parse(localStorage.getItem('calendarData') || '{}');
    let categories = JSON.parse(localStorage.getItem('calendarCategories') || '{}');
    if (categories['reel']) delete categories['reel'];
    let hashtags = JSON.parse(localStorage.getItem('calendarHashtags') || '{}');
    if (Array.isArray(hashtags)) {
      const temp = {};
      hashtags.forEach(h => temp[h] = '#6b7280');
      hashtags = temp;
    }
    let formats = JSON.parse(localStorage.getItem('calendarFormats') || '{}');
    let persons = JSON.parse(localStorage.getItem('calendarPersons') || '{}');
    let networks = JSON.parse(localStorage.getItem('calendarNetworks') || '{}');
    let selectedDate = '';
    let currentImageData = '';
    let currentVideoData = '';
    let currentFilter = 'all';
    let currentHashtag = 'all';
    let currentFormat = 'all';
    let currentPerson = 'all';
    let currentNetwork = 'all';
    let calendarName = localStorage.getItem('calendarName') || 'Calendario Social';
    document.getElementById('calendarName').value = calendarName;
    document.getElementById('pageTitle').textContent = calendarName + ' con Categorías Dinámicas';
    document.getElementById('calendarName').addEventListener('change', () => {
      calendarName = document.getElementById('calendarName').value || 'Calendario Social';
      localStorage.setItem('calendarName', calendarName);
      document.getElementById('pageTitle').textContent = calendarName + ' con Categorías Dinámicas';
    });

    function defaultCategories() {
      return {
        receta: '#10b981',
        institucional: '#3b82f6',
        promociones: '#ef4444',
        'efemérides': '#8b5cf6'
      };
    }

    function defaultHashtags() {
      return { '#promo': '#6b7280', '#marketing': '#6b7280' };
    }

    function defaultFormats() {
      return { Feed: '#6b7280', Stories: '#f97316', Reels: '#facc15', 'En Vivo': '#10b981' };
    }

    function defaultPersons() {
      return { 'Equipo': '#0ea5e9' };
    }

    function defaultNetworks() {
      return {
        Facebook: {color: '#1877f2', icon: '📘'},
        Instagram: {color: '#e1306c', icon: '📸'},
        TikTok: {color: '#000000', icon: '🎵'},
        YouTube: {color: '#ff0000', icon: '▶️'},
        LinkedIn: {color: '#0a66c2', icon: '💼'}
      };
    }

    if (Object.keys(categories).length === 0) {
      categories = defaultCategories();
    }
    if (Object.keys(hashtags).length === 0) {
      hashtags = defaultHashtags();
    }
    if (Object.keys(formats).length === 0) {
      formats = defaultFormats();
    }
    if (Object.keys(persons).length === 0) {
      persons = defaultPersons();
    }
    if (Object.keys(networks).length === 0) {
      networks = defaultNetworks();
    }

    function pruneMedia(data) {
      const copy = JSON.parse(JSON.stringify(data));
      Object.values(copy).forEach(days => {
        Object.values(days).forEach(d => {
          if (d.image && d.image.startsWith('data:')) delete d.image;
          if (Array.isArray(d.images)) d.images = d.images.filter(img => !img.startsWith('data:'));
          if (d.video && d.video.startsWith('data:')) delete d.video;
        });
      });
      return copy;
    }

    function saveData() {
      try {
        localStorage.setItem('calendarData', JSON.stringify(calendarData));
      } catch (e) {
        if (e.name === 'QuotaExceededError') {
          const trimmed = pruneMedia(calendarData);
          try {
            localStorage.setItem('calendarData', JSON.stringify(trimmed));
            alert('El calendario es demasiado grande y se guardó sin imágenes ni videos.');
            console.warn('Datos demasiado grandes, se guardaron sin imágenes/videos');
          } catch (e2) {
            console.error('No se pudo guardar calendarData', e2);
          }
        } else {
          console.error('Error guardando datos', e);
        }
      }
      localStorage.setItem('calendarCategories', JSON.stringify(categories));
      localStorage.setItem('calendarHashtags', JSON.stringify(hashtags));
      localStorage.setItem('calendarFormats', JSON.stringify(formats));
      localStorage.setItem('calendarPersons', JSON.stringify(persons));
      localStorage.setItem('calendarNetworks', JSON.stringify(networks));
      localStorage.setItem('calendarName', calendarName);
    }

    function capitalize(s) {
      return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function closeAllModals() {
      ['modal','categoryModal','hashtagModal','formatModal','personModal','networkModal','duplicateModal','dataModal','previewModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
      });
    }

    function getAspectRatio(format, network) {
      network = network || 'Instagram';
      if (network === 'Instagram') {
        return ['Stories','Reels','En Vivo'].includes(format) ? 'aspect-[9/16]' : 'aspect-square';
      }
      if (network === 'TikTok') return 'aspect-[9/16]';
      if (network === 'YouTube') return 'aspect-video';
      if (network === 'Facebook' || network === 'LinkedIn') {
        if (format === 'Stories') return 'aspect-[9/16]';
        return 'aspect-video';
      }
      return (format === 'Feed' || format === 'Carrusel') ? 'aspect-square' : 'aspect-[9/16]';
    }

    function buildInstagramPreview(info, aspect, showIcons = true, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1
        ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>`
        : '';
      const tags = Array.isArray(info.hashtags) ? info.hashtags.map(h => `#${h}`).join(' ') : '';
      if (minimal) {
        return `<div class="instagram-card"><div class="relative w-full ${aspect}">${mediaContent}</div></div>`;
      }
      return `<div class="instagram-card"><div class="instagram-header"><div class="instagram-avatar"></div><span class="text-xs font-bold">Instagram</span></div><div class="relative w-full ${aspect}">${mediaContent}${nav}</div>${showIcons ? `<div class="instagram-icons">❤ 💬 ➤</div>` : ''}<div class="instagram-caption">${info.copy || info.content || ''} ${tags}</div></div>`;
    }

    function buildFacebookPreview(info, aspect, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1 ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>` : '';
      if (minimal) {
        return `<div class="facebook-card"><div class="relative w-full ${aspect}">${mediaContent}</div></div>`;
      }
      return `<div class="facebook-card"><div class="facebook-header">Facebook</div><div class="relative w-full ${aspect}">${mediaContent}${nav}</div><div class="facebook-caption">${info.copy || info.content || ''}</div></div>`;
    }

    function buildTikTokPreview(info, aspect, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1 ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>` : '';
      if (minimal) {
        return `<div class="tiktok-card"><div class="relative w-full ${aspect}">${mediaContent}</div></div>`;
      }
      return `<div class="tiktok-card"><div class="tiktok-header">TikTok</div><div class="relative w-full ${aspect}">${mediaContent}${nav}</div><div class="tiktok-caption">${info.copy || info.content || ''}</div></div>`;
    }

    function buildYouTubePreview(info, aspect, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1 ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>` : '';
      if (minimal) {
        return `<div class="youtube-card"><div class="relative w-full ${aspect}">${mediaContent}</div></div>`;
      }
      return `<div class="youtube-card"><div class="youtube-header">YouTube</div><div class="relative w-full ${aspect}">${mediaContent}${nav}</div><div class="youtube-caption">${info.copy || info.content || ''}</div></div>`;
    }

    function buildLinkedInPreview(info, aspect, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1 ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>` : '';
      if (minimal) {
        return `<div class="linkedin-card"><div class="relative w-full ${aspect}">${mediaContent}</div></div>`;
      }
      return `<div class="linkedin-card"><div class="linkedin-header">LinkedIn</div><div class="relative w-full ${aspect}">${mediaContent}${nav}</div><div class="linkedin-caption">${info.copy || info.content || ''}</div></div>`;
    }

    function buildGenericPreview(info, aspect, minimal = false) {
      const img = Array.isArray(info.images) ? info.images[0] : info.image;
      const multi = Array.isArray(info.images) && info.images.length > 1;
      const overlay = multi && minimal ? `<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${info.images.length-1}</div>` : '';
      const videoIsImage = info.video && info.video.startsWith('data:image');
      const videoTag = videoIsImage ? `<img id="previewImg" src="${info.video}" class="object-cover w-full h-full"/>` : `<video controls class="w-full h-full"><source src="${info.video}"></video>`;
      const media = img ? `<img id="previewImg" src="${img}" class="object-cover w-full h-full"/>${overlay}` : (info.video ? videoTag : '');
      const fallback = '<div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">Sin imagen</div>';
      const mediaContent = media || fallback;
      const nav = Array.isArray(info.images) && info.images.length > 1 ? `<button onclick='changePreview(-1)' class='absolute left-1 top-1 bg-white/50 px-1'>&lt;</button><button onclick='changePreview(1)' class='absolute right-1 top-1 bg-white/50 px-1'>&gt;</button>` : '';
      if (minimal) {
        return `<div class="relative w-full ${aspect}">${mediaContent}</div>`;
      }
      return `<div class="relative w-full ${aspect}">${mediaContent}${nav}</div>`;
    }

    function buildPreview(info, aspect, showIcons = true, minimal = false) {
      const net = info.network || 'Instagram';
      switch(net) {
        case 'Instagram':
          return buildInstagramPreview(info, aspect, showIcons, minimal);
        case 'Facebook':
          return buildFacebookPreview(info, aspect, minimal);
        case 'TikTok':
          return buildTikTokPreview(info, aspect, minimal);
        case 'YouTube':
          return buildYouTubePreview(info, aspect, minimal);
        case 'LinkedIn':
          return buildLinkedInPreview(info, aspect, minimal);
        default:
          return buildGenericPreview(info, aspect, minimal);
      }
    }

    function updateSelectors() {
      const modalSelect = document.getElementById('modal-type');
      modalSelect.innerHTML = '<option value="">Tipo</option>';
      Object.keys(categories).forEach(key => {
        modalSelect.innerHTML += `<option value="${key}">${capitalize(key)}</option>`;
      });

      const tagSelect = document.getElementById('modal-hashtags');
      if (tagSelect) {
        tagSelect.innerHTML = Object.keys(hashtags).map(h => `<option value="${h}">${h}</option>`).join('');
      }

      const fmtSelect = document.getElementById('modal-format');
      if (fmtSelect) {
        fmtSelect.innerHTML = '<option value="">Formato</option>';
        Object.keys(formats).forEach(f => {
          fmtSelect.innerHTML += `<option value="${f}">${f}</option>`;
        });
        fmtSelect.addEventListener('change', formatChanged);
      }

      const netSelect = document.getElementById('modal-network');
      if (netSelect) {
        netSelect.innerHTML = '<option value="">Red Social</option>';
        Object.keys(networks).forEach(n => {
          netSelect.innerHTML += `<option value="${n}">${n}</option>`;
        });
        netSelect.addEventListener('change', updateEditPreviewAspect);
      }

      const personSelect = document.getElementById('modal-persons');
      if (personSelect) {
        personSelect.innerHTML = Object.keys(persons).map(p => `<option value="${p}">${p}</option>`).join('');
      }
    }

    function addHashtagToList(tag) {
      if (!tag) return;
      if (!tag.startsWith('#')) tag = '#' + tag;
      if (!hashtags[tag]) {
        hashtags[tag] = '#6b7280';
        const opt = document.createElement('option');
        opt.value = tag;
        opt.textContent = tag;
        document.getElementById('modal-hashtags').appendChild(opt);
      }
      Array.from(document.getElementById('modal-hashtags').options).forEach(o => {
        if (o.value === tag) o.selected = true;
      });
      saveData();
    }

    function handleHashtagInput(e) {
      if (e.target.value.includes(',')) {
        const parts = e.target.value.split(',');
        for (let i = 0; i < parts.length - 1; i++) {
          const tag = parts[i].trim();
          if (tag) addHashtagToList(tag);
        }
        e.target.value = parts[parts.length - 1].trim();
      }
    }

    function addPersonToList(name) {
      if (!name) return;
      if (!persons[name]) {
        persons[name] = '#0ea5e9';
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        document.getElementById('modal-persons').appendChild(opt);
      }
      Array.from(document.getElementById('modal-persons').options).forEach(o => {
        if (o.value === name) o.selected = true;
      });
      saveData();
    }

    function handlePersonInput(e) {
      if (e.target.value.includes(',')) {
        const parts = e.target.value.split(',');
        for (let i = 0; i < parts.length - 1; i++) {
          const name = parts[i].trim();
          if (name) addPersonToList(name);
        }
        e.target.value = parts[parts.length - 1].trim();
      }
    }

    async function convertImageToWebp(file) {
      return new Promise(resolve => {
        const img = new Image();
        img.onload = () => {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          canvas.getContext('2d').drawImage(img, 0, 0);
          canvas.toBlob(b => {
            const r = new FileReader();
            r.onload = ev => resolve(ev.target.result);
            r.readAsDataURL(b);
          }, 'image/webp');
        };
        img.src = URL.createObjectURL(file);
      });
    }

    async function convertVideoToWebP(file) {
      return await new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.muted = true;
        video.src = url;
        video.addEventListener('loadeddata', () => {
          const canvas = document.createElement('canvas');
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          canvas.getContext('2d').drawImage(video, 0, 0);
          canvas.toBlob(b => {
            const r = new FileReader();
            r.onload = e => resolve(e.target.result);
            r.readAsDataURL(b);
            URL.revokeObjectURL(url);
          }, 'image/webp', 0.2);
        }, { once: true });
        video.addEventListener('error', err => reject(err), { once: true });
      });
    }

    async function handleImageFile(e) {
      const files = Array.from(e.target.files || []);
      if (files.length === 0) return;
      const promises = files.map(f => convertImageToWebp(f));
      const imgs = await Promise.all(promises);
      currentImageData = document.getElementById('modal-carousel').checked ? imgs : imgs[0];
      const wrapper = document.getElementById('editImageWrapper');
      const prev = document.getElementById('modal-image-preview');
      const fmt = document.getElementById('modal-format').value || 'Feed';
      const net = document.getElementById('modal-network').value || 'Instagram';
      wrapper.className = `relative w-full ${getAspectRatio(fmt, net)} hidden`;
      prev.src = Array.isArray(currentImageData) ? currentImageData[0] : currentImageData;
      wrapper.classList.remove('hidden');
      document.getElementById('prevImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
      document.getElementById('nextImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
      document.getElementById('modal-image-url').value = '';
      updateEditPreviewAspect();
      renderEditPreview();
    }

    function handleImageUrl(e) {
      if (document.getElementById('modal-carousel').checked) {
        currentImageData = e.target.value.split(',').map(s => s.trim()).filter(Boolean);
      } else {
        currentImageData = e.target.value;
      }
      const wrapper = document.getElementById('editImageWrapper');
      const prev = document.getElementById('modal-image-preview');
      const fmt = document.getElementById('modal-format').value || 'Feed';
      const net = document.getElementById('modal-network').value || 'Instagram';
      wrapper.className = `relative w-full ${getAspectRatio(fmt, net)} hidden`;
      if (currentImageData && (Array.isArray(currentImageData) ? currentImageData[0] : currentImageData)) {
        prev.src = Array.isArray(currentImageData) ? currentImageData[0] : currentImageData;
        wrapper.classList.remove('hidden');
        document.getElementById('prevImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
        document.getElementById('nextImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
      } else {
        wrapper.classList.add('hidden');
        document.getElementById('prevImgBtn').classList.add('hidden');
        document.getElementById('nextImgBtn').classList.add('hidden');
      }
      updateEditPreviewAspect();
      renderEditPreview();
    }

    async function handleVideoFile(e) {
      const file = e.target.files[0];
      if (!file) return;
      currentVideoData = await convertVideoToWebP(file);
      const vprev = document.getElementById('modal-video-preview');
      vprev.src = currentVideoData;
      vprev.classList.remove('hidden');
      document.getElementById('modal-video-url').value = '';
      renderEditPreview();
    }

    function handleVideoUrl(e) {
      currentVideoData = e.target.value;
      const vprev = document.getElementById('modal-video-preview');
      if (currentVideoData) {
        vprev.src = currentVideoData;
        vprev.classList.remove('hidden');
      } else {
        vprev.classList.add('hidden');
      }
      renderEditPreview();
    }

    function toggleCarousel() {
      const fileInput = document.getElementById('modal-image-file');
      if (document.getElementById('modal-carousel').checked) {
        fileInput.setAttribute('multiple', 'multiple');
      } else {
        fileInput.removeAttribute('multiple');
      }
      updateEditPreviewAspect();
    }

    let editImgIndex = 0;
    function changeEditImage(dir) {
      if (!Array.isArray(currentImageData) || currentImageData.length<=1) return;
      editImgIndex = (editImgIndex + dir + currentImageData.length) % currentImageData.length;
      document.getElementById('modal-image-preview').src = currentImageData[editImgIndex];
      renderEditPreview();
    }

    function formatChanged() {
      const fmt = document.getElementById('modal-format').value;
      const show = fmt === 'Feed' || fmt === 'Stories';
      document.getElementById('modal-carousel').parentElement.style.display = show ? 'flex' : 'none';
      updateEditPreviewAspect();
    }

    function updateEditPreviewAspect() {
      const fmt = document.getElementById('modal-format').value || 'Feed';
      const net = document.getElementById('modal-network').value || 'Instagram';
      const wrapper = document.getElementById('editImageWrapper');
      if (wrapper) {
        wrapper.className = `relative w-full ${getAspectRatio(fmt, net)} hidden`;
        const hasImg = currentImageData && (Array.isArray(currentImageData) ? currentImageData[0] : currentImageData);
        if (hasImg) wrapper.classList.remove('hidden');
      }
      renderEditPreview();
    }

    function renderEditPreview() {
      const fmt = document.getElementById('modal-format').value || 'Feed';
      const net = document.getElementById('modal-network').value || 'Instagram';
      const info = {
        network: net,
        format: fmt,
        image: Array.isArray(currentImageData) ? undefined : currentImageData,
        images: Array.isArray(currentImageData) ? currentImageData : undefined,
        video: currentVideoData,
        content: document.getElementById('modal-content').value,
        copy: document.getElementById('modal-copy').value
      };
      const aspect = getAspectRatio(fmt, net);
      document.getElementById('editPreview').innerHTML = buildPreview(info, aspect, true);
      if (!document.getElementById('previewDetails').open) {
        document.getElementById('editThumb').innerHTML = buildPreview(info, aspect, false, true);
      }
    }

function togglePreview() {
      const det = document.getElementById('previewDetails');
      if (det.open) {
        document.getElementById('editThumb').classList.add('hidden');
      } else {
        renderEditPreview();
        document.getElementById('editThumb').classList.remove('hidden');
}
document.getElementById('previewDetails').addEventListener('toggle', togglePreview);
    }

    function renderLegend() {
      const catLeg = document.getElementById('categoryLegend');
      catLeg.innerHTML = '';
      const allCat = document.createElement('span');
      allCat.className = 'tag';
      allCat.style.backgroundColor = '#9ca3af';
      allCat.textContent = 'Todos';
      allCat.onclick = () => { currentFilter = 'all'; filterCalendar(); };
      catLeg.appendChild(allCat);
      Object.entries(categories).forEach(([key, color]) => {
        const s = document.createElement('span');
        s.className = 'tag';
        s.style.backgroundColor = color;
        s.textContent = capitalize(key);
        s.onclick = () => { currentFilter = key; filterCalendar(); };
        s.setAttribute('draggable', 'true');
        s.ondragstart = (e) => startTagDrag(e, 'type', key);
        catLeg.appendChild(s);
      });

      const hashLeg = document.getElementById('hashtagLegend');
      hashLeg.innerHTML = '';
      const allHash = document.createElement('span');
      allHash.className = 'tag';
      allHash.style.backgroundColor = '#9ca3af';
      allHash.textContent = '#Todos';
      allHash.onclick = () => { currentHashtag = 'all'; filterCalendar(); };
      hashLeg.appendChild(allHash);
      Object.entries(hashtags).forEach(([h,color]) => {
        const s = document.createElement('span');
        s.className = 'tag';
        s.style.backgroundColor = color;
        s.textContent = h;
        s.onclick = () => { currentHashtag = h; filterCalendar(); };
        s.setAttribute('draggable','true');
        s.ondragstart = (e) => startTagDrag(e,'hashtag',h);
        hashLeg.appendChild(s);
      });

      const fmtLeg = document.getElementById('formatLegend');
      if (fmtLeg) {
        // include formats found in data
        Object.values(calendarData).forEach(days => {
          Object.values(days).forEach(d => {
            if (d.format && !formats[d.format]) formats[d.format] = '#6b7280';
          });
        });
        fmtLeg.innerHTML = '';
        const allFmt = document.createElement('span');
        allFmt.className = 'tag';
        allFmt.style.backgroundColor = '#9ca3af';
        allFmt.textContent = 'Todos';
        allFmt.onclick = () => { currentFormat = 'all'; filterCalendar(); };
        fmtLeg.appendChild(allFmt);
        Object.entries(formats).forEach(([f,color]) => {
          const s = document.createElement('span');
          s.className = 'tag';
          s.style.backgroundColor = color;
          s.textContent = f;
          s.onclick = () => { currentFormat = f; filterCalendar(); };
          s.setAttribute('draggable','true');
          s.ondragstart = (e) => startTagDrag(e,'format',f);
          fmtLeg.appendChild(s);
        });
      }

      const personLeg = document.getElementById('personLegend');
      if (personLeg) {
        personLeg.innerHTML = '';
        const allPer = document.createElement('span');
        allPer.className = 'tag';
        allPer.style.backgroundColor = '#9ca3af';
        allPer.textContent = 'Todos';
        allPer.onclick = () => { currentPerson = 'all'; filterCalendar(); };
        personLeg.appendChild(allPer);
        Object.entries(persons).forEach(([p,c]) => {
          const s = document.createElement('span');
          s.className = 'tag';
          s.style.backgroundColor = c;
          s.textContent = p;
          s.onclick = () => { currentPerson = p; filterCalendar(); };
          s.setAttribute('draggable','true');
          s.ondragstart = (e) => startTagDrag(e,'person',p);
          personLeg.appendChild(s);
        });
      }

      const netLeg = document.getElementById('networkLegend');
      if (netLeg) {
        netLeg.innerHTML = '';
        const allNet = document.createElement('span');
        allNet.className = 'tag';
        allNet.style.backgroundColor = '#9ca3af';
        allNet.textContent = 'Todos';
        allNet.onclick = () => { currentNetwork = 'all'; filterCalendar(); };
        netLeg.appendChild(allNet);
        Object.entries(networks).forEach(([n,obj]) => {
          const s = document.createElement('span');
          s.className = 'tag';
          s.style.backgroundColor = obj.color;
          s.textContent = `${obj.icon} ${n}`;
          s.onclick = () => { currentNetwork = n; filterCalendar(); };
          s.setAttribute('draggable','true');
          s.ondragstart = (e) => startTagDrag(e,'network',n);
          netLeg.appendChild(s);
        });
      }
    }

    function renderCalendar() {
      saveData();
      updateSelectors();
      renderLegend();
      const calendar = document.getElementById('calendar');
      const [year, month] = document.getElementById('monthPicker').value.split('-').map(Number);
      const monthKey = `${year}-${String(month).padStart(2, '0')}`;
      const firstDay = new Date(year, month - 1, 1).getDay();
      const daysInMonth = new Date(year, month, 0).getDate();
      calendar.innerHTML = '';
      ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'].forEach(d => calendar.innerHTML += `<div class='text-center font-bold'>${d}</div>`);
      for (let i = 0; i < firstDay; i++) calendar.innerHTML += '<div></div>';
      for (let d = 1; d <= daysInMonth; d++) {
        const data = calendarData[monthKey]?.[d] || {};
        const color = data.type && categories[data.type] ? categories[data.type] : '#ccc';
        const info = encodeURIComponent(JSON.stringify(data));
        const hasData = Object.keys(data).length > 0;
        const format = data.format || 'Feed';
        const net = data.network || 'Instagram';
        const aspect = getAspectRatio(format, net);
        let mediaHTML = '';
        if (Array.isArray(data.images) && data.images.length) {
          mediaHTML = `<div class="relative w-full ${aspect}"><img src="${data.images[0]}" class="object-cover w-full h-full rounded"/>${data.images.length>1?`<div class='absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-0.5 rounded'>+${data.images.length-1}</div>`:''}</div>`;
        } else if (data.image) {
          mediaHTML = `<div class="w-full ${aspect}"><img src="${data.image}" class="object-cover w-full h-full rounded"/></div>`;
        }
        calendar.innerHTML += `<div class="border p-2 rounded grid-day bg-white hover:bg-blue-50 cursor-pointer text-xs space-y-0.5" draggable="true" ondragstart="startDrag(event,'${monthKey}',${d})" ondragover="allowDrop(event)" ondrop="dropDay(event,'${monthKey}',${d})" data-type="${data.type || ''}" data-key="${monthKey}" data-day="${d}" onclick="openPreviewModal('${monthKey}', ${d})" onmouseover="showTooltip(event,this)" onmouseout="hideTooltip()">
          <div class="flex justify-between items-center">
            <div class="font-bold text-sm">${d}</div>
            <div class="space-x-1">
              <span class="cursor-pointer" onclick="openEditModal('${monthKey}',${d});event.stopPropagation();" title="Editar">✏️</span>
              <span class="cursor-pointer" onclick="prepareDuplicate('${monthKey}',${d});event.stopPropagation();" title="Copiar">📄</span>
              <span class="cursor-pointer" onclick="event.stopPropagation();" title="Mover (arrastrar)">↔️</span>
              <span class="cursor-pointer" onclick="deleteDay(event,'${monthKey}',${d})" title="Borrar">🗑️</span>
            </div>
          </div>
          ${hasData ? '' : '<div class="text-gray-300 text-center">➕</div>'}
          ${data.time ? `<div>⏰ ${data.time}</div>` : ''}
          ${data.content ? `<div class="truncate">📝 ${data.content}</div>` : ''}
          ${data.theme ? `<div class="truncate">🎨 ${data.theme}</div>` : ''}
          ${mediaHTML}
          ${data.format ? `<span class='tag' style='background:${formats[data.format] || '#6b7280'}'>${data.format}</span>` : ''}
          ${data.type ? `<span class='tag' style='background:${color}'>${capitalize(data.type)}</span>` : ''}
          ${Array.isArray(data.hashtags) ? data.hashtags.map(h=>`<span class='tag' style='background:${hashtags[h] || '#9ca3af'}'>${h}</span>`).join(' ') : ''}
          ${Array.isArray(data.persons) ? data.persons.map(p=>`<span class='tag' style='background:${persons[p] || '#9ca3af'}'>${p}</span>`).join(' ') : ''}
          ${data.network ? `<span class='tag' style='background:${networks[data.network]?.color || '#6b7280'}'>${networks[data.network]?.icon || ''}</span>` : ''}
        </div>`;
      }
      filterCalendar();
    }

    let previewImages = [];
    let previewIndex = 0;

    function openPreviewModal(key, day) {
      closeAllModals();
      selectedDate = { key, day };
      const data = calendarData[key]?.[day] || {};
      document.getElementById('previewDate').textContent = `${key}-${String(day).padStart(2,'0')}`;
      const media = document.getElementById('previewMedia');
      media.innerHTML = '';
      const format = data.format || 'Feed';
      const net = data.network || 'Instagram';
      const aspect = getAspectRatio(format, net);
      previewImages = [];
      if (data.images && data.images.length) {
        previewImages = data.images;
      } else if (data.image) {
        previewImages = [data.image];
      }
      previewIndex = 0;
      media.innerHTML = buildPreview({...data, network: net}, aspect, true);
      const info = document.getElementById('previewInfo');
      info.innerHTML = '';
      if (data.time) info.innerHTML += `<div>⏰ ${data.time}</div>`;
      if (data.content) info.innerHTML += `<div>📝 ${data.content}</div>`;
      if (data.theme) info.innerHTML += `<div>🎨 ${data.theme}</div>`;
      if (data.format) info.innerHTML += `<span class='tag' style='background:${formats[data.format]||'#6b7280'}'>${data.format}</span>`;
      if (data.type) info.innerHTML += ` <span class='tag' style='background:${categories[data.type]||'#6b7280'}'>${capitalize(data.type)}</span>`;
      if (data.network) info.innerHTML += ` <span class='tag' style='background:${networks[data.network]?.color || '#6b7280'}'>${networks[data.network]?.icon || ''}</span>`;
      if (Array.isArray(data.hashtags)) info.innerHTML += `<div>${data.hashtags.map(h=>`<span class='tag' style='background:${hashtags[h]||'#9ca3af'}'>${h}</span>`).join(' ')}</div>`;
      if (Array.isArray(data.persons)) info.innerHTML += `<div>${data.persons.map(p=>`<span class='tag' style='background:${persons[p]||'#9ca3af'}'>${p}</span>`).join(' ')}</div>`;
      document.getElementById('previewModal').classList.remove('hidden');
    }

    function changePreview(dir) {
      if (!previewImages.length) return;
      previewIndex = (previewIndex + dir + previewImages.length) % previewImages.length;
      document.getElementById('previewImg').src = previewImages[previewIndex];
    }

    function closePreviewModal() {
      document.getElementById('previewModal').classList.add('hidden');
    }

    function openEditModal(key, day) {
      closeAllModals();
      selectedDate = { key, day };
      const data = calendarData[key]?.[day] || {};
      document.getElementById('modal-day').textContent = `${key}-${String(day).padStart(2, '0')}`;
      document.getElementById('modal-type').value = data.type || '';
      document.getElementById('modal-content').value = data.content || '';
      document.getElementById('modal-time').value = data.time || '';
      document.getElementById('modal-theme').value = data.theme || '';
      document.getElementById('modal-visual').value = data.visual || '';
      document.getElementById('modal-format').value = data.format || '';
      document.getElementById('modal-network').value = data.network || '';
      formatChanged();
      document.getElementById('modal-copy').value = data.copy || '';
      document.getElementById('modal-copystories').value = data.copyStories || '';
      document.getElementById('modal-source').value = data.source || '';
      document.getElementById('modal-objective').value = data.objective || '';
      currentImageData = data.images || data.image || '';
      const carousel = Array.isArray(currentImageData);
      document.getElementById('modal-carousel').checked = carousel;
      toggleCarousel();
      editImgIndex = 0;
      document.getElementById('modal-image-file').value = '';
      if (carousel) {
        document.getElementById('modal-image-url').value = Array.isArray(currentImageData) ? currentImageData.join(',') : '';
      } else {
        document.getElementById('modal-image-url').value = currentImageData && !String(currentImageData).startsWith('data:') ? currentImageData : '';
      }
      const wrapper = document.getElementById('editImageWrapper');
      const preview = document.getElementById('modal-image-preview');
      const aspect = getAspectRatio(data.format || 'Feed', data.network || 'Instagram');
      wrapper.className = `relative w-full ${aspect} hidden`;
      if (currentImageData && (Array.isArray(currentImageData) ? currentImageData[0] : currentImageData)) {
        preview.src = Array.isArray(currentImageData) ? currentImageData[0] : currentImageData;
        wrapper.classList.remove('hidden');
        document.getElementById('prevImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
        document.getElementById('nextImgBtn').classList.toggle('hidden', !Array.isArray(currentImageData) || currentImageData.length<=1);
      } else {
        wrapper.classList.add('hidden');
        document.getElementById('prevImgBtn').classList.add('hidden');
        document.getElementById('nextImgBtn').classList.add('hidden');
      }
      updateEditPreviewAspect();
      currentVideoData = data.video || '';
      document.getElementById('modal-video-file').value = '';
      document.getElementById('modal-video-url').value = currentVideoData && !String(currentVideoData).startsWith('data:') ? currentVideoData : '';
      const vprev = document.getElementById('modal-video-preview');
      if (currentVideoData) { vprev.src = currentVideoData; vprev.classList.remove('hidden'); } else { vprev.classList.add('hidden'); }
      document.getElementById('modal-hashtag-input').value = '';
      document.getElementById('modal-person-input').value = '';
      const tagSelect = document.getElementById('modal-hashtags');
      if (tagSelect) {
        Array.from(tagSelect.options).forEach(o => o.selected = Array.isArray(data.hashtags) && data.hashtags.includes(o.value));
      }
      const personSelect = document.getElementById('modal-persons');
      if (personSelect) {
        Array.from(personSelect.options).forEach(o => o.selected = Array.isArray(data.persons) && data.persons.includes(o.value));
      }
      renderEditPreview();
      document.getElementById('modal').classList.remove('hidden');
    }

    function closeEditModal() {
      document.getElementById('modal').classList.add('hidden');
    }

    function saveChanges() {
      const { key, day } = selectedDate;
      if (!calendarData[key]) calendarData[key] = {};
      const isCarousel = document.getElementById('modal-carousel').checked;
      calendarData[key][day] = {
        type: document.getElementById('modal-type').value,
        content: document.getElementById('modal-content').value,
        time: document.getElementById('modal-time').value,
        theme: document.getElementById('modal-theme').value,
        visual: document.getElementById('modal-visual').value,
        format: document.getElementById('modal-format').value,
        network: document.getElementById('modal-network').value,
        copy: document.getElementById('modal-copy').value,
        copyStories: document.getElementById('modal-copystories').value,
        source: document.getElementById('modal-source').value,
        objective: document.getElementById('modal-objective').value,
        hashtags: Array.from(document.getElementById('modal-hashtags').selectedOptions).map(o => o.value),
        persons: Array.from(document.getElementById('modal-persons').selectedOptions).map(o=>o.value),
        image: isCarousel ? '' : (Array.isArray(currentImageData) ? currentImageData[0] : currentImageData),
        images: isCarousel ? (Array.isArray(currentImageData) ? currentImageData : (currentImageData ? [currentImageData] : [])) : undefined,
        video: currentVideoData
      };
      closeEditModal();
      renderCalendar();
    }

   function openDuplicateModal() {
      closeAllModals();
      createDuplicateFieldOptions();
      const [y,m] = selectedDate.key.split('-');
      document.getElementById('duplicate-date').value = `${y}-${m}-${String(selectedDate.day).padStart(2,'0')}`;
      document.getElementById('duplicateModal').classList.remove('hidden');
    }

    function prepareDuplicate(key, day) {
      selectedDate = { key, day };
      openDuplicateModal();
    }

    function createDuplicateFieldOptions() {
      const container = document.getElementById('duplicateFields');
      container.innerHTML = '';
      copyFieldDefs.forEach(f => {
        const lbl = document.createElement('label');
        lbl.className = 'flex items-center gap-2';
        lbl.innerHTML = `<input type="checkbox" value="${f.key}" checked> ${f.label}`;
        container.appendChild(lbl);
      });
    }

    function closeDuplicateModal() {
      document.getElementById('duplicateModal').classList.add('hidden');
    }

    function confirmDuplicate() {
      const target = document.getElementById('duplicate-date').value;
      if (!target) return;
      const [y,m,d] = target.split('-');
      const key = `${y}-${m}`;
      if (!calendarData[key]) calendarData[key] = {};
      if (!calendarData[key][parseInt(d)]) calendarData[key][parseInt(d)] = {};
      const fields = Array.from(document.querySelectorAll('#duplicateFields input:checked')).map(i=>i.value);
      const src = calendarData[selectedDate.key]?.[selectedDate.day] || {};
      const dest = calendarData[key][parseInt(d)];
      fields.forEach(f => {
        if (f === 'image' && src.images !== undefined) {
          dest.images = JSON.parse(JSON.stringify(src.images));
        }
        if (src[f] !== undefined) dest[f] = JSON.parse(JSON.stringify(src[f]));
      });
      closeDuplicateModal();
      closeEditModal();
      renderCalendar();
    }

   function openCategoryModal() {
      closeAllModals();
      const list = document.getElementById('categoryList');
      list.innerHTML = '';
      Object.entries(categories).forEach(([key, color]) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2';
        li.innerHTML = `<input value="${key}" class="border p-1 flex-1 cat-name">
                        <input type="color" value="${color}" class="cat-color">
                        <button onclick="deleteCategory('${key}')" class='text-red-500 text-sm'>Eliminar</button>`;
        list.appendChild(li);
      });
      document.getElementById('categoryModal').classList.remove('hidden');
    }

    function saveCategoryEdits() {
      const items = document.querySelectorAll('#categoryList li');
      const newCats = {};
      items.forEach(li=>{
        const name = li.querySelector('.cat-name').value.trim();
        const color = li.querySelector('.cat-color').value;
        if (name) newCats[name] = color;
      });
      categories = newCats;
      updateSelectors();
      renderCalendar();
    }

    function closeCategoryModal() {
      document.getElementById('categoryModal').classList.add('hidden');
    }

   function openHashtagModal() {
      closeAllModals();
      const list = document.getElementById('hashtagList');
      list.innerHTML = '';
      Object.entries(hashtags).forEach(([h,color]) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2';
        li.innerHTML = `<input value="${h}" class="border p-1 flex-1 hash-name"> <input type="color" value="${color}" class="hash-color"> <button onclick="deleteHashtag('${h}')" class='text-red-500 text-sm'>Eliminar</button>`;
        list.appendChild(li);
      });
      document.getElementById('hashtagModal').classList.remove('hidden');
    }

    function closeHashtagModal() {
      document.getElementById('hashtagModal').classList.add('hidden');
    }

    function saveHashtagEdits() {
      const items = document.querySelectorAll('#hashtagList li');
      const newTags = {};
      items.forEach(li=>{
        let name = li.querySelector('.hash-name').value.trim();
        if (name && !name.startsWith('#')) name = '#' + name;
        const color = li.querySelector('.hash-color').value;
        if (name) newTags[name] = color;
      });
      hashtags = newTags;
      updateSelectors();
      renderCalendar();
    }

    function addHashtag() {
      let tag = document.getElementById('newHashtag').value.trim();
      const color = document.getElementById('newHashtagColor').value;
      if (tag) {
        if (!tag.startsWith('#')) tag = '#' + tag;
        if (!hashtags[tag]) {
          hashtags[tag] = color || '#6b7280';
          document.getElementById('newHashtag').value = '';
          updateSelectors();
          openHashtagModal();
        }
      }
    }

    function deleteHashtag(tag) {
      delete hashtags[tag];
      updateSelectors();
      openHashtagModal();
    }

   function openFormatModal() {
      closeAllModals();
      const list = document.getElementById('formatList');
      list.innerHTML = '';
      Object.entries(formats).forEach(([f,color]) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2';
        li.innerHTML = `<input value="${f}" class="border p-1 flex-1 format-name"> <input type="color" value="${color}" class="format-color"> <button onclick="deleteFormat('${f}')" class='text-red-500 text-sm'>Eliminar</button>`;
        list.appendChild(li);
      });
      document.getElementById('formatModal').classList.remove('hidden');
    }

    function closeFormatModal() {
      document.getElementById('formatModal').classList.add('hidden');
    }

    function saveFormatEdits() {
      const items = document.querySelectorAll('#formatList li');
      const newFormats = {};
      items.forEach(li=>{
        const name = li.querySelector('.format-name').value.trim();
        const color = li.querySelector('.format-color').value;
        if (name) newFormats[name] = color;
      });
      formats = newFormats;
      updateSelectors();
      renderCalendar();
    }

    function addFormat() {
      const name = document.getElementById('newFormatName').value.trim();
      const color = document.getElementById('newFormatColor').value;
      if (name && color && !formats[name]) {
        formats[name] = color;
        document.getElementById('newFormatName').value = '';
        updateSelectors();
        openFormatModal();
      }
    }

    function deleteFormat(name) {
      delete formats[name];
      updateSelectors();
      openFormatModal();
    }

   function openPersonModal() {
      closeAllModals();
      const list = document.getElementById('personList');
      list.innerHTML = '';
      Object.entries(persons).forEach(([p,c]) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2';
        li.innerHTML = `<input value="${p}" class="border p-1 flex-1 person-name"> <input type="color" value="${c}" class="person-color"> <button onclick="deletePerson('${p}')" class='text-red-500 text-sm'>Eliminar</button>`;
        list.appendChild(li);
      });
      document.getElementById('personModal').classList.remove('hidden');
    }

    function closePersonModal() {
      document.getElementById('personModal').classList.add('hidden');
    }

    function savePersonEdits() {
      const items = document.querySelectorAll('#personList li');
      const newPersons = {};
      items.forEach(li => {
        const name = li.querySelector('.person-name').value.trim();
        const color = li.querySelector('.person-color').value;
        if (name) newPersons[name] = color;
      });
      persons = newPersons;
      updateSelectors();
      renderCalendar();
    }

    function addPerson() {
      const name = document.getElementById('newPersonName').value.trim();
      const color = document.getElementById('newPersonColor').value;
      if (name && color && !persons[name]) {
        persons[name] = color;
        document.getElementById('newPersonName').value = '';
        updateSelectors();
        openPersonModal();
      }
    }

    function deletePerson(name) {
      delete persons[name];
      updateSelectors();
      openPersonModal();
    }

   function openNetworkModal() {
      closeAllModals();
      const list = document.getElementById('networkList');
      list.innerHTML = '';
      Object.entries(networks).forEach(([n,obj]) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2';
        li.innerHTML = `<input value="${n}" class="border p-1 flex-1 net-name"> <input value="${obj.icon}" class="border p-1 w-12 net-icon"> <input type="color" value="${obj.color}" class="net-color"> <button onclick="deleteNetwork('${n}')" class='text-red-500 text-sm'>Eliminar</button>`;
        list.appendChild(li);
      });
      document.getElementById('networkModal').classList.remove('hidden');
    }

    function closeNetworkModal() {
      document.getElementById('networkModal').classList.add('hidden');
    }

    function saveNetworkEdits() {
      const items = document.querySelectorAll('#networkList li');
      const newNets = {};
      items.forEach(li => {
        const name = li.querySelector('.net-name').value.trim();
        const icon = li.querySelector('.net-icon').value.trim() || '';
        const color = li.querySelector('.net-color').value;
        if (name) newNets[name] = {icon,color};
      });
      networks = newNets;
      updateSelectors();
      renderCalendar();
    }

    function addNetwork() {
      const name = document.getElementById('newNetworkName').value.trim();
      const icon = document.getElementById('newNetworkIcon').value.trim();
      const color = document.getElementById('newNetworkColor').value;
      if (name && color && !networks[name]) {
        networks[name] = {icon,color};
        document.getElementById('newNetworkName').value = '';
        updateSelectors();
        openNetworkModal();
      }
    }

    function deleteNetwork(name) {
      delete networks[name];
      updateSelectors();
      openNetworkModal();
    }

    const fieldList = [
      {key:'calendarName', label:'Nombre del Calendario'},
      {key:'type', label:'Tipo'},
      {key:'content', label:'Contenido'},
      {key:'time', label:'Hora'},
      {key:'theme', label:'Temática'},
      {key:'visual', label:'Visual'},
      {key:'image', label:'Imagen'},
      {key:'video', label:'Video'},
      {key:'format', label:'Formato'},
      {key:'network', label:'Red'},
      {key:'copy', label:'Copy'},
      {key:'copyStories', label:'Copy Stories'},
      {key:'source', label:'Fuente'},
      {key:'objective', label:'Objetivo'},
      {key:'hashtags', label:'Hashtags'},
      {key:'persons', label:'Personas'},
      {key:'categories', label:'Categorías'}
    ];

    const copyFieldDefs = fieldList.filter(f => !['calendarName','categories'].includes(f.key));

    function createFieldOptions() {
      const container = document.getElementById('fieldOptions');
      container.innerHTML = '';
      fieldList.forEach(f => {
        const lbl = document.createElement('label');
        lbl.className = 'flex items-center gap-2';
        lbl.innerHTML = `<input type="checkbox" value="${f.key}" checked> ${f.label}`;
        container.appendChild(lbl);
      });
    }

    function getSelectedFields() {
      return Array.from(document.querySelectorAll('#fieldOptions input:checked')).map(i => i.value);
    }

   function openDataModal() {
      closeAllModals();
      createFieldOptions();
      document.getElementById('dataModal').classList.remove('hidden');
    }

    function closeDataModal() {
      document.getElementById('dataModal').classList.add('hidden');
    }

    function addCategory() {
      const name = document.getElementById('newCategoryName').value.trim();
      const color = document.getElementById('newCategoryColor').value;
      if (name && color && !categories[name]) {
        categories[name] = color;
        document.getElementById('newCategoryName').value = '';
        renderCalendar();
      }
    }

    function deleteCategory(key) {
      delete categories[key];
      renderCalendar();
      openCategoryModal();
    }

    function filterCalendar() {
      document.querySelectorAll('#calendar > div[data-type]').forEach(cell => {
        const info = calendarData[cell.dataset.key]?.[parseInt(cell.dataset.day)] || {};
        const byType = currentFilter === 'all' || cell.dataset.type === currentFilter;
        const byHash = currentHashtag === 'all' || (Array.isArray(info.hashtags) && info.hashtags.includes(currentHashtag));
        const byFormat = currentFormat === 'all' || info.format === currentFormat;
      const byPerson = currentPerson === 'all' || (Array.isArray(info.persons) && info.persons.includes(currentPerson));
      const byNetwork = currentNetwork === 'all' || info.network === currentNetwork;
      cell.style.display = byType && byHash && byFormat && byPerson && byNetwork ? 'block' : 'none';
      });
    }

    const tooltip = document.getElementById('tooltip');

    function showTooltip(event, el) {
      let info = {};
      try {
        const key = el.dataset.key;
        const day = parseInt(el.dataset.day);
        info = calendarData[key]?.[day] || {};
      } catch (e) {}
      const net = info.network || 'Instagram';
      const aspect = getAspectRatio(info.format || 'Feed', net);
      const html = buildPreview({...info, network: net}, aspect, false, true);
      tooltip.innerHTML = html;
      tooltip.style.left = event.pageX + 10 + 'px';
      tooltip.style.top = event.pageY + 10 + 'px';
      tooltip.classList.remove('hidden');
    }

    function hideTooltip() {
      tooltip.classList.add('hidden');
    }

    function copyField(id) {
      const el = document.getElementById(id);
      if (!el) return;
      let val = '';
      if (el.tagName === 'SELECT') {
        if (el.multiple) {
          val = Array.from(el.selectedOptions).map(o => o.value).join(',');
        } else {
          val = el.value;
        }
      } else {
        val = el.value || '';
      }
      navigator.clipboard.writeText(val);
    }

    function pasteField(id) {
      const el = document.getElementById(id);
      if (!el) return;
      navigator.clipboard.readText().then(text => {
        if (el.tagName === 'SELECT') {
          if (el.multiple) {
            const vals = text.split(',').map(s => s.trim());
            Array.from(el.options).forEach(o => o.selected = vals.includes(o.value));
          } else {
            el.value = text;
          }
        } else {
          el.value = text;
        }
        if (typeof el.oninput === 'function') el.oninput();
      });
    }

    function changeMonth(diff) {
      const picker = document.getElementById('monthPicker');
      let [y, m] = picker.value.split('-').map(Number);
      m += diff;
      if (m < 1) { m = 12; y--; }
      if (m > 12) { m = 1; y++; }
      picker.value = `${y}-${String(m).padStart(2,'0')}`;
      renderCalendar();
    }

    function goToday() {
      const now = new Date();
      const month = String(now.getMonth() + 1).padStart(2,'0');
      const year = now.getFullYear();
      document.getElementById('monthPicker').value = `${year}-${month}`;
      renderCalendar();
    }

    function clearMonth() {
      const monthKey = document.getElementById('monthPicker').value;
      if (confirm(`¿Estás seguro que querés borrar todos los datos de ${monthKey}?`)) {
        calendarData[monthKey] = {};
        renderCalendar();
      }
    }

    function deleteDay(e, key, day) {
      e.stopPropagation();
      if (calendarData[key] && calendarData[key][day] && confirm('¿Borrar este día?')) {
        delete calendarData[key][day];
        renderCalendar();
      }
    }

    function clearAll() {
      if (confirm('¿Borrar todos los meses y configuraciones?')) {
        calendarData = {};
        categories = defaultCategories();
        hashtags = defaultHashtags();
        formats = defaultFormats();
        calendarName = 'Calendario Social';
        document.getElementById('calendarName').value = calendarName;
        document.getElementById('pageTitle').textContent = calendarName + ' con Categorías Dinámicas';
        localStorage.removeItem('calendarData');
        localStorage.removeItem('calendarCategories');
        localStorage.removeItem('calendarHashtags');
        localStorage.removeItem('calendarFormats');
        localStorage.removeItem('calendarName');
        renderCalendar();
      }
    }

    function exportData(type) {
      const fields = getSelectedFields();
      const start = document.getElementById('rangeStart').value;
      const end = document.getElementById('rangeEnd').value;
      const header = ['Fecha', ...fields.filter(f=>!['categories','calendarName','image','video'].includes(f)).map(f=>fieldList.find(x=>x.key===f).label)];
      const rows = [];
      const imageRows = [['Fecha','Imagen']];
      const videoRows = [['Fecha','Video']];
      if (fields.includes('calendarName')) rows.push(['Nombre Calendario', calendarName]);
      rows.push(header);
      Object.entries(calendarData).forEach(([monthKey, days]) => {
        Object.entries(days).forEach(([day, data]) => {
          const date = `${monthKey}-${String(day).padStart(2,'0')}`;
          if ((start && date < start) || (end && date > end)) return;
          const row = [date];
          fields.forEach(f => {
            if (['categories','calendarName','image','video'].includes(f)) return;
            const val = Array.isArray(data[f]) ? data[f].join(' ') : (data[f] || '');
            row.push(val);
          });
          if (fields.includes('image')) {
            if (data.images) imageRows.push([date, data.images.join('|')]);
            else if (data.image) imageRows.push([date, data.image]);
          }
          if (fields.includes('video') && data.video) {
            videoRows.push([date, data.video]);
          }
          rows.push(row);
        });
      });

      if (fields.includes('categories')) {
        rows.push([]);
        rows.push(['Categoria','Color']);
        Object.entries(categories).forEach(([k,c]) => rows.push([k,c]));
      }

      if (type === 'csv') {
        const csv = rows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], {type: 'text/csv'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'calendario.csv';
        a.click();
        if (fields.includes('image')) {
          const csvImg = imageRows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
          const blobImg = new Blob([csvImg], {type: 'text/csv'});
          const urlImg = URL.createObjectURL(blobImg);
          const aImg = document.createElement('a');
          aImg.href = urlImg;
          aImg.download = 'calendario_imagenes.csv';
          aImg.click();
        }
        if (fields.includes('video')) {
          const csvVid = videoRows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
          const blobVid = new Blob([csvVid], {type: 'text/csv'});
          const urlVid = URL.createObjectURL(blobVid);
          const aVid = document.createElement('a');
          aVid.href = urlVid;
          aVid.download = 'calendario_videos.csv';
          aVid.click();
        }
      } else {
        const ws = XLSX.utils.aoa_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Calendario');
        if (fields.includes('categories')) {
          const catRows = [['Categoria','Color'], ...Object.entries(categories)];
          const ws2 = XLSX.utils.aoa_to_sheet(catRows);
          XLSX.utils.book_append_sheet(wb, ws2, 'Categorias');
        }
        if (fields.includes('calendarName')) {
          const info = [['Nombre Calendario', calendarName]];
          const wsInfo = XLSX.utils.aoa_to_sheet(info);
          XLSX.utils.book_append_sheet(wb, wsInfo, 'Info');
        }
        if (fields.includes('image')) {
          const wsImg = XLSX.utils.aoa_to_sheet(imageRows);
          XLSX.utils.book_append_sheet(wb, wsImg, 'Imagenes');
        }
        if (fields.includes('video')) {
          const wsVid = XLSX.utils.aoa_to_sheet(videoRows);
          XLSX.utils.book_append_sheet(wb, wsVid, 'Videos');
        }
        XLSX.writeFile(wb, 'calendario.xlsx');
      }
    }

    async function exportImage(fmt) {
      const canvas = await html2canvas(document.getElementById('calendar'));
      const link = document.createElement('a');
      let mime = 'image/png';
      if (fmt === 'jpg') mime = 'image/jpeg';
      else if (fmt === 'webp') mime = 'image/webp';
      link.href = canvas.toDataURL(mime);
      link.download = `calendario.${fmt}`;
      link.click();
    }

    async function exportPDF() {
      const canvas = await html2canvas(document.getElementById('calendar'));
      const imgData = canvas.toDataURL('image/png');
      const pdf = new jspdf.jsPDF('landscape');
      const width = pdf.internal.pageSize.getWidth();
      const height = canvas.height * width / canvas.width;
      pdf.addImage(imgData, 'PNG', 0, 0, width, height);
      pdf.save('calendario.pdf');
    }

    function startTagDrag(e, field, value) {
      e.dataTransfer.setData('tag', JSON.stringify({field, value}));
    }

    let dragSource = null;
    function startDrag(e, key, day) {
      dragSource = { key, day };
    }
    function allowDrop(e) {
      e.preventDefault();
    }
    function dropDay(e, key, day) {
      e.preventDefault();
      const tagData = e.dataTransfer.getData('tag');
      if (tagData) {
        const {field, value} = JSON.parse(tagData);
        if (!calendarData[key]) calendarData[key] = {};
        if (!calendarData[key][day]) calendarData[key][day] = {};
        if (field === 'type' || field === 'format' || field === 'network') {
          calendarData[key][day][field === 'type' ? 'type' : field] = value;
        } else if (field === 'hashtag') {
          const arr = calendarData[key][day].hashtags || [];
          if (!arr.includes(value)) arr.push(value);
          calendarData[key][day].hashtags = arr;
        } else if (field === 'person') {
          const arr = calendarData[key][day].persons || [];
          if (!arr.includes(value)) arr.push(value);
          calendarData[key][day].persons = arr;
        }
        renderCalendar();
        return;
      }
      if (!dragSource) return;
      const srcData = calendarData[dragSource.key]?.[dragSource.day];
      if (!srcData) return;
      const destData = calendarData[key]?.[day];
      const isCopy = e.shiftKey && !e.ctrlKey;
      const isForceMove = e.shiftKey && e.ctrlKey;
      if (destData && Object.keys(destData).length && !isCopy && !isForceMove) {
        alert('El día de destino ya tiene contenido');
        dragSource = null;
        return;
      }
      if (!calendarData[key]) calendarData[key] = {};
      calendarData[key][day] = JSON.parse(JSON.stringify(srcData));
      if (isForceMove || (!destData && !isCopy)) {
        delete calendarData[dragSource.key][dragSource.day];
      }
      dragSource = null;
      renderCalendar();
    }

const labelToKey = Object.fromEntries(fieldList.map(f => [f.label, f.key]));

    function parseCSVLine(line) {
      const out = [];
      let cur = '';
      let inQuotes = false;
      for (let i=0; i<line.length; i++) {
        const ch = line[i];
        if (ch === '"') { inQuotes = !inQuotes; continue; }
        if (ch === ',' && !inQuotes) { out.push(cur); cur = ''; } else { cur += ch; }
      }
      out.push(cur);
      return out.map(s => s.replace(/""/g,'"').trim());
    }

    function processCSVText(text) {
      const selected = getSelectedFields();
      const start = document.getElementById('rangeStart').value;
      const end = document.getElementById('rangeEnd').value;
      const lines = text.split('\n');
        let i = 0;
        if (selected.includes('calendarName') && lines[i].startsWith('"Nombre Calendario"')) {
          const parts = parseCSVLine(lines[i]);
          calendarName = parts[1] || calendarName;
          document.getElementById('calendarName').value = calendarName;
          document.getElementById('pageTitle').textContent = calendarName + ' con Categorías Dinámicas';
          i++;
        }
        const headers = parseCSVLine(lines[i++]);
        while (i < lines.length) {
          const line = lines[i++];
          if (!line.trim()) break;
          if (line.startsWith('Categoria')) { i--; break; }
          const cells = parseCSVLine(line);
          const fecha = cells[0];
          if (!fecha) continue;
          if ((start && fecha < start) || (end && fecha > end)) continue;
          const [y,m,d] = fecha.split('-');
          const key = `${y}-${m}`;
          if (!calendarData[key]) calendarData[key] = {};
          const obj = {};
          headers.slice(1).forEach((h,idx) => {
            const k = labelToKey[h];
            if (!k || !selected.includes(k)) return;
            let val = cells[idx+1] || '';
            if (k === 'hashtags') {
              val = val ? val.split(/\s+/) : [];
              val.forEach(tag => { if (tag && !hashtags[tag]) hashtags[tag] = '#6b7280'; });
            } else if (k === 'image') {
              if (val.includes('|')) {
                obj['images'] = val.split('|').map(s=>s.trim());
                val = '';
              }
            } else if (k === 'persons') {
              val = val ? val.split(/\s+/) : [];
              val.forEach(p=>{ if(p && !persons[p]) persons[p] = '#0ea5e9'; });
            } else if (k === 'video') {
              // single video base64 or url
            }
            obj[k] = val;
          });
          calendarData[key][parseInt(d)] = Object.assign(calendarData[key][parseInt(d)]||{}, obj);
        }

        if (selected.includes('categories')) {
          // search for categories section
          while (i < lines.length && !lines[i].startsWith('Categoria')) i++;
          if (i < lines.length && lines[i].startsWith('Categoria')) {
            i++;
            for (; i < lines.length; i++) {
              const cline = lines[i].trim();
              if (!cline) continue;
              const [name,color] = cline.split(',');
              if (name) categories[name.trim()] = color?.trim() || '#000000';
            }
          }
        }
        renderCalendar();
    }

    function importCSV(event) {
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = e => processCSVText(e.target.result);
      reader.readAsText(file);
    }

    function importCSVFromUrl() {
      const url = document.getElementById('csvUrl').value.trim();
      if (!url) return;
      fetch(url).then(r=>r.text()).then(t=>processCSVText(t));
    }

    function importCSVFromText() {
      const t = document.getElementById('csvText').value;
      if (t) processCSVText(t);
    }

    function importImagesCSV(event) {
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        const lines = e.target.result.split('\n').slice(1);
        lines.forEach(l => {
          if (!l.trim()) return;
          let parts = [];
          let current = '';
          let inQuotes = false;
          for (let ch of l) {
            if (ch === '"') { inQuotes = !inQuotes; continue; }
            if (ch === ',' && !inQuotes) { parts.push(current); current = ''; } else { current += ch; }
          }
          parts.push(current);
          const fecha = parts[0]?.trim();
          const img = parts.slice(1).join(',').trim();
          if (!fecha || !img) return;
          const [y,m,d] = fecha.split('-');
          const key = `${y}-${m}`;
          if (!calendarData[key]) calendarData[key] = {};
          if (!calendarData[key][parseInt(d)]) calendarData[key][parseInt(d)] = {};
          if (img.includes('|')) {
            calendarData[key][parseInt(d)].images = img.split('|').map(s=>s.trim());
          } else {
            calendarData[key][parseInt(d)].image = img;
          }
        });
        renderCalendar();
      };
      reader.readAsText(file);
    }

    function importVideosCSV(event) {
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        const lines = e.target.result.split('\n').slice(1);
        lines.forEach(l => {
          if (!l.trim()) return;
          const idx = l.indexOf(',');
          if (idx === -1) return;
          const fecha = l.slice(0, idx).trim();
          const vid = l.slice(idx+1).trim();
          if (!fecha || !vid) return;
          const [y,m,d] = fecha.split('-');
          const key = `${y}-${m}`;
          if (!calendarData[key]) calendarData[key] = {};
          if (!calendarData[key][parseInt(d)]) calendarData[key][parseInt(d)] = {};
          calendarData[key][parseInt(d)].video = vid;
        });
        renderCalendar();
      };
      reader.readAsText(file);
    }

    function importExcel(event) {
      const selected = getSelectedFields();
      const start = document.getElementById('rangeStart').value;
      const end = document.getElementById('rangeEnd').value;
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        if (selected.includes('calendarName')) {
          const infoSheet = workbook.Sheets['Info'];
          if (infoSheet) {
            const infoRows = XLSX.utils.sheet_to_json(infoSheet, {header:1});
            if (infoRows[0] && infoRows[0][1]) {
              calendarName = infoRows[0][1];
              document.getElementById('calendarName').value = calendarName;
              document.getElementById('pageTitle').textContent = calendarName + ' con Categorías Dinámicas';
            }
          }
        }
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet, {header:1});
        const headers = rows[0];
        for (let i=1; i<rows.length; i++) {
          const row = rows[i];
          if (!row[0]) break;
          if (row[0] === 'Categoria') break;
          const fecha = row[0];
          if ((start && fecha < start) || (end && fecha > end)) continue;
          const [y,m,d] = fecha.split('-');
          const key = `${y}-${m}`;
          if (!calendarData[key]) calendarData[key] = {};
          const obj = {};
          headers.slice(1).forEach((h,idx) => {
            const k = labelToKey[h];
            if (!k || !selected.includes(k)) return;
            let val = row[idx+1] || '';
            if (k === 'hashtags') {
              val = val ? String(val).split(/\s+/) : [];
              val.forEach(tag => { if (tag && !hashtags[tag]) hashtags[tag] = '#6b7280'; });
            } else if (k === 'persons') {
              val = val ? String(val).split(/\s+/) : [];
              val.forEach(p=>{ if(p && !persons[p]) persons[p] = '#0ea5e9'; });
            } else if (k === 'image') {
              if (String(val).includes('|')) {
                obj['images'] = String(val).split('|').map(s=>s.trim());
                val = '';
              }
            } else if (k === 'video') {
              // single video string
            }
            obj[k] = val;
          });
          calendarData[key][parseInt(d)] = Object.assign(calendarData[key][parseInt(d)]||{}, obj);
        }

        if (selected.includes('categories')) {
          const catSheet = workbook.Sheets['Categorias'];
          if (catSheet) {
            const catRows = XLSX.utils.sheet_to_json(catSheet, {header:1});
            catRows.slice(1).forEach(r => {
              if (!r[0]) return;
              categories[r[0]] = r[1] || '#000000';
            });
          }
        }
        renderCalendar();
      };
      reader.readAsArrayBuffer(file);
    }

    document.getElementById('monthPicker').addEventListener('change', renderCalendar);
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        ['modal','categoryModal','hashtagModal','formatModal','personModal','networkModal','duplicateModal','dataModal','previewModal'].forEach(id => {
          const el = document.getElementById(id);
          if (el && !el.classList.contains('hidden')) el.classList.add('hidden');
        });
      }
    });
    renderCalendar();
  </script>
</body>
</html>
