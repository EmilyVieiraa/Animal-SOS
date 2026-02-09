<?php
declare(strict_types=1);

$old = $old ?? [];
?>

<section class="report-page">
  <div class="report-container">

    <h1>Reportar animal em risco</h1>

    <?php if (!empty($erro)): ?>
      <p class="report-error"><?= htmlspecialchars((string)$erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form
      method="post" action="<?= BASE_URL ?>/index.php?c=animal&a=reportar" enctype="multipart/form-data" class="report-form"
    >
      <div class="form-group">
        <label>Título da denúncia *</label>
        <input
          type="text" name="titulo" required maxlength="120" value="<?= htmlspecialchars((string)($old['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          placeholder="Ex.: Cachorro ferido próximo ao mercado"
        >
      </div>

      <div class="form-group">
        <label for="rep_fotos">Adicionar imagens</label>
        <small class="helper-text">Máximo de 5 fotos não repetidas, até 2MB cada.</small>
        <input id="rep_fotos" type="file" name="fotos[]" accept="image/*" multiple>

        <div class="report-previews" id="photoPreview"></div>
      </div>

      <div class="form-group">
        <label>Espécie *</label>
        <?php $v = (string)($old['especie'] ?? ''); ?>
        <select name="especie" required>
          <option value="">Selecione</option>
          <option value="Cachorro" <?= $v === 'Cachorro' ? 'selected' : '' ?>>Cachorro</option>
          <option value="Gato" <?= $v === 'Gato' ? 'selected' : '' ?>>Gato</option>
          <option value="Outro" <?= $v === 'Outro' ? 'selected' : '' ?>>Outro</option>
        </select>
      </div>

      <div class="form-group">
        <label>Cor</label>
        <input
          type="text"
          name="cor"
          value="<?= htmlspecialchars((string)($old['cor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          placeholder="Ex.: Marrom claro"
        >
      </div>

      <div class="form-group">
        <label>Condição do animal</label>
        <?php $c = (string)($old['condicao'] ?? ''); ?>

        <label class="radio-line">
          <input type="radio" name="condicao" value="Aparentemente saudável" <?= $c === 'Aparentemente saudável' ? 'checked' : '' ?>>
          Aparentemente saudável
        </label>

        <label class="radio-line">
          <input type="radio" name="condicao" value="Muito debilitado" <?= $c === 'Muito debilitado' ? 'checked' : '' ?>>
          Muito debilitado
        </label>

        <label class="radio-line">
          <input type="radio" name="condicao" value="Ferido" <?= $c === 'Ferido' ? 'checked' : '' ?>>
          Ferido
        </label>
      </div>

      <div class="form-group">
        <label>Descrição</label>
        <textarea
          name="descricao"
          rows="4"
          placeholder="Descreva aqui..."
        ><?= htmlspecialchars((string)($old['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="form-group">
        <label>CEP *</label>
        <input type="text" id="rep_cep" name="cep"
              value="<?= htmlspecialchars((string)($old['cep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="00000-000" maxlength="9" inputmode="numeric">
        <small class="helper-text" id="repCepHelp" style="display:none;"></small>
      </div>

      <div class="form-group">
        <label>Rua *</label>
        <input type="text" id="rep_rua" name="rua" required
              value="<?= htmlspecialchars((string)($old['rua'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Nome da rua">
      </div>

      <div class="form-group">
        <label>Número *</label>
        <input type="text" id="rep_numero" name="numero" required
              value="<?= htmlspecialchars((string)($old['numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Número">
      </div>

      <div class="form-group">
        <label>Bairro *</label>
        <input type="text" id="rep_bairro" name="bairro" required
              value="<?= htmlspecialchars((string)($old['bairro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Bairro">
      </div>

      <div class="form-group">
        <label>Cidade *</label>
        <input type="text" id="rep_cidade" name="cidade" required
              value="<?= htmlspecialchars((string)($old['cidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Cidade">
      </div>

      <div class="form-group">
        <label for="rep_estado">Estado *</label>
        <div class="select-wrapper">
          <?php $uf = (string)($old['estado'] ?? ''); ?>
          <select id="rep_estado" name="estado" required>
            <option value="">Selecione o estado</option>
            <option value="AC" <?= $uf==='AC'?'selected':'' ?>>Acre</option>
            <option value="AL" <?= $uf==='AL'?'selected':'' ?>>Alagoas</option>
            <option value="AP" <?= $uf==='AP'?'selected':'' ?>>Amapá</option>
            <option value="AM" <?= $uf==='AM'?'selected':'' ?>>Amazonas</option>
            <option value="BA" <?= $uf==='BA'?'selected':'' ?>>Bahia</option>
            <option value="CE" <?= $uf==='CE'?'selected':'' ?>>Ceará</option>
            <option value="DF" <?= $uf==='DF'?'selected':'' ?>>Distrito Federal</option>
            <option value="ES" <?= $uf==='ES'?'selected':'' ?>>Espírito Santo</option>
            <option value="GO" <?= $uf==='GO'?'selected':'' ?>>Goiás</option>
            <option value="MA" <?= $uf==='MA'?'selected':'' ?>>Maranhão</option>
            <option value="MT" <?= $uf==='MT'?'selected':'' ?>>Mato Grosso</option>
            <option value="MS" <?= $uf==='MS'?'selected':'' ?>>Mato Grosso do Sul</option>
            <option value="MG" <?= $uf==='MG'?'selected':'' ?>>Minas Gerais</option>
            <option value="PA" <?= $uf==='PA'?'selected':'' ?>>Pará</option>
            <option value="PB" <?= $uf==='PB'?'selected':'' ?>>Paraíba</option>
            <option value="PR" <?= $uf==='PR'?'selected':'' ?>>Paraná</option>
            <option value="PE" <?= $uf==='PE'?'selected':'' ?>>Pernambuco</option>
            <option value="PI" <?= $uf==='PI'?'selected':'' ?>>Piauí</option>
            <option value="RJ" <?= $uf==='RJ'?'selected':'' ?>>Rio de Janeiro</option>
            <option value="RN" <?= $uf==='RN'?'selected':'' ?>>Rio Grande do Norte</option>
            <option value="RS" <?= $uf==='RS'?'selected':'' ?>>Rio Grande do Sul</option>
            <option value="RO" <?= $uf==='RO'?'selected':'' ?>>Rondônia</option>
            <option value="RR" <?= $uf==='RR'?'selected':'' ?>>Roraima</option>
            <option value="SC" <?= $uf==='SC'?'selected':'' ?>>Santa Catarina</option>
            <option value="SP" <?= $uf==='SP'?'selected':'' ?>>São Paulo</option>
            <option value="SE" <?= $uf==='SE'?'selected':'' ?>>Sergipe</option>
            <option value="TO" <?= $uf==='TO'?'selected':'' ?>>Tocantins</option>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary" id="btnEnviarDenuncia">
          Enviar denúncia
        </button>
      </div>

    </form>
  </div>
</section>

<!-- Script de prévia da imagem selecionada -->
<script>
  (function () {
  const form = document.querySelector('form.report-form');
  let inputRef = document.getElementById('rep_fotos');
  const preview = document.getElementById('photoPreview');
  if (!form || !inputRef || !preview) return;

  const MAX_FILES = 5;
  const MAX_SIZE  = 2 * 1024 * 1024;

  let dt = new DataTransfer();

  function sync() { inputRef.files = dt.files; }

  function render() {
    preview.innerHTML = '';
    Array.from(dt.files).forEach((file, i) => {
      const item = document.createElement('div');
      item.className = 'report-preview-item';

      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Prévia';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'report-preview-remove';
      btn.textContent = '×';
      btn.addEventListener('click', () => {
        const next = new DataTransfer();
        Array.from(dt.files).forEach((f, idx) => { if (idx !== i) next.items.add(f); });
        dt = next;
        sync();
        render();
      });

      item.appendChild(img);
      item.appendChild(btn);
      preview.appendChild(item);
    });
  }

  function addFiles(files) {
    for (const f of files) {
      if (!f.type.startsWith('image/')) continue;

      if (f.size > MAX_SIZE) {
        alert(`"${f.name}" excede 2MB.`);
        continue;
      }

      const dup = Array.from(dt.files).some(x =>
        x.name === f.name && x.size === f.size && x.lastModified === f.lastModified
      );
      if (dup) continue;

      if (dt.files.length >= MAX_FILES) {
        alert(`Máximo de ${MAX_FILES} imagens.`);
        break;
      }

      dt.items.add(f);
    }

    sync();
    render();
  }

  function bindChange(el) {
    el.addEventListener('change', () => {
      if (el.files && el.files.length) addFiles(el.files);

      // clone seguro para permitir escolher o mesmo arquivo novamente
      const clone = el.cloneNode(true);
      el.parentNode.replaceChild(clone, el);

      inputRef = clone;
      sync();
      bindChange(inputRef);
    });
  }

  bindChange(inputRef);

  form.addEventListener('submit', () => {
    sync();
  });
})();
</script>

<!-- Script de busca de endereço via CEP -->
<script>
  (function () {
    const cepInput    = document.getElementById('rep_cep');
    const ruaInput    = document.getElementById('rep_rua');
    const numInput    = document.getElementById('rep_numero');
    const bairroInput = document.getElementById('rep_bairro');
    const cidadeInput = document.getElementById('rep_cidade');
    const estadoSel   = document.getElementById('rep_estado');
    const cepHelp     = document.getElementById('repCepHelp');

    if (!cepInput || !ruaInput || !cidadeInput || !estadoSel) return;

    function onlyDigits(s){ return (s || '').replace(/\D/g, ''); }

    function setHelp(msg, isError){
      if (!cepHelp) return;
      cepHelp.style.display = msg ? 'block' : 'none';
      cepHelp.textContent = msg || '';
      cepHelp.style.color = isError ? '#b71c1c' : 'rgba(0,0,0,.65)';
      cepHelp.style.fontWeight = isError ? '700' : '400';
    }

    async function fetchCep(cep){
      const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`, { method: 'GET' });
      if (!res.ok) throw new Error('Falha ao consultar CEP.');
      return await res.json();
    }

    function fillAddress(data){
      ruaInput.value = data.logradouro || '';
      if (bairroInput) bairroInput.value = data.bairro || '';
      cidadeInput.value = data.localidade || '';
      estadoSel.value = data.uf || '';
      if (numInput) numInput.focus();
    }

    // máscara 00000-000
    cepInput.addEventListener('input', function(){
      let v = onlyDigits(cepInput.value).slice(0,8);
      if (v.length > 5) v = v.slice(0,5) + '-' + v.slice(5);
      cepInput.value = v;
      setHelp('', false);
    });

    let lastCep = '';
    async function tryLookup(){
      const cep = onlyDigits(cepInput.value);

      if (cep.length === 0) { setHelp('', false); return; }
      if (cep.length !== 8) { setHelp('CEP incompleto.', true); return; }
      if (cep === lastCep) return;

      lastCep = cep;
      setHelp('Consultando CEP...', false);

      try{
        const data = await fetchCep(cep);
        if (data.erro) { setHelp('CEP não encontrado.', true); return; }
        fillAddress(data);
        setHelp('Endereço preenchido automaticamente.', false);
      } catch (e){
        setHelp('Não foi possível consultar o CEP no momento.', true);
      }
    }

    cepInput.addEventListener('blur', tryLookup);
    cepInput.addEventListener('keyup', function(){
      if (onlyDigits(cepInput.value).length === 8) tryLookup();
    });
  })();
</script>
