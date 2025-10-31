<!doctype html>
<html lang="zh">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>条码识别测试 · Camera Scan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#0f1115; color:#e9ecef; }
    .appbar { position:sticky; top:0; background:#151820; border-bottom:1px solid #2a2f3a; z-index:10;}
    .panel { background:#151b24; border:1px solid #2a2f3a; border-radius:12px; }
    video { width:100%; border-radius:12px; background:#000; }
    .result { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .pill { background:#223; border:1px solid #2a2f3a; border-radius:999px; padding:.25rem .6rem; }
    .small-muted{ color:#98a2b3; }
    canvas { display:none; } /* 仅在原生检测时用来抓帧 */
  </style>
</head>
<body>
<div class="appbar py-2 px-3 d-flex justify-content-between align-items-center">
  <div class="fw-bold">📷 条码识别测试</div>
  <div id="modeBadge" class="pill small-muted">初始化中…</div>
</div>

<div class="container py-3">
  <div class="panel p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-12">
        <label class="form-label">摄像头</label>
        <select id="cameraSelect" class="form-select"></select>
      </div>
      <div class="col-12 d-flex gap-2">
        <button id="btnStart" class="btn btn-primary flex-fill">开始扫描</button>
        <button id="btnStop" class="btn btn-outline-light flex-fill" disabled>停止</button>
      </div>
      <div class="col-12 d-flex gap-2">
        <button id="btnTorch" class="btn btn-outline-warning flex-fill" disabled>手电/补光</button>
        <button id="btnFlip" class="btn btn-outline-light flex-fill">切换前/后摄</button>
      </div>
    </div>
  </div>

  <div class="panel p-2 mb-3">
    <video id="video" playsinline muted></video>
    <canvas id="canvas"></canvas>
  </div>

  <div class="panel p-3">
    <div class="mb-2">识别结果：</div>
    <div id="result" class="result h5 mb-2">—</div>
    <div id="symtype" class="small-muted">类型：—</div>
    <div id="perf" class="small-muted mt-2"></div>
  </div>
</div>

<script>
/* ---- 基本引用与状态 ---- */
const $ = s=>document.querySelector(s);
const video = $('#video');
const canvas = $('#canvas');
const cameraSelect = $('#cameraSelect');
const btnStart = $('#btnStart');
const btnStop  = $('#btnStop');
const btnTorch = $('#btnTorch');
const btnFlip  = $('#btnFlip');
const resultEl = $('#result');
const typeEl   = $('#symtype');
const perfEl   = $('#perf');
const modeBadge= $('#modeBadge');

let currentStream = null;
let usingNative = false;      // 是否使用原生 BarcodeDetector
let zxingReader = null;       // ZXing reader
let zxingActive = false;
let scanLoopTimer = null;
let lastDetected = '';
let lastFacingMode = 'environment'; // 优先后置

/* ---- 工具：列出摄像头 ---- */
async function listCameras() {
  const devices = await navigator.mediaDevices.enumerateDevices();
  const cams = devices.filter(d=>d.kind==='videoinput');
  cameraSelect.innerHTML = '';
  cams.forEach((d,i)=>{
    const opt = document.createElement('option');
    opt.value = d.deviceId;
    opt.textContent = d.label || `摄像头 ${i+1}`;
    cameraSelect.appendChild(opt);
  });
}

/* ---- 打开摄像头 ---- */
async function openCamera({deviceId=null, facingMode='environment'}={}) {
  if (currentStream) {
    currentStream.getTracks().forEach(t=>t.stop());
    currentStream = null;
  }
  const constraints = {
    audio: false,
    video: deviceId ? {deviceId: {exact: deviceId}} : {
      facingMode: facingMode,
      width: {ideal: 1280},
      height:{ideal: 720}
    }
  };
  currentStream = await navigator.mediaDevices.getUserMedia(constraints);
  video.srcObject = currentStream;
  await video.play();

  // 若设备支持 torch，允许按钮
  const track = currentStream.getVideoTracks()[0];
  const caps = track.getCapabilities ? track.getCapabilities() : {};
  if (caps.torch) {
    btnTorch.disabled = false;
  } else {
    btnTorch.disabled = true;
  }
}

/* ---- Torch 开关 ---- */
async function toggleTorch() {
  if (!currentStream) return;
  const track = currentStream.getVideoTracks()[0];
  const caps = track.getCapabilities ? track.getCapabilities() : {};
  if (!caps.torch) return;
  const settings = track.getSettings ? track.getSettings() : {};
  const cur = !!settings.torch;
  try {
    await track.applyConstraints({ advanced: [{ torch: !cur }] });
  } catch(e) {
    // 某些浏览器用 fillLightMode
    try {
      await track.applyConstraints({advanced: [{ fillLightMode: cur ? 'off' : 'flash' }]});
    } catch(_){}
  }
}

/* ---- 原生 BarcodeDetector 扫描循环 ---- */
let detector = null;
async function startNativeLoop() {
  if (!('BarcodeDetector' in window)) return false;
  // 可选格式：常见一维码 + QR
  const formats = [
    'ean-13','ean-8','upc-a','upc-e',
    'code-128','code-39','code-93','itf','codabar',
    'qr-code'
  ].filter(f => BarcodeDetector.getSupportedFormats
                ? BarcodeDetector.getSupportedFormats().includes(f)
                : true);
  try {
    detector = new BarcodeDetector({ formats });
  } catch(e) { return false; }

  usingNative = true;
  modeBadge.textContent = '原生识别（BarcodeDetector）';
  modeBadge.classList.remove('pill','small-muted');
  modeBadge.classList.add('pill');
  let lastTime = performance.now();

  const ctx = canvas.getContext('2d', { willReadFrequently: true });

  const loop = async () => {
    if (!currentStream) return;
    if (video.readyState >= 2) {
      canvas.width  = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const t0 = performance.now();
      try {
        const bitmaps = [canvas];
        const codes = await detector.detect(canvas);
        const t1 = performance.now();
        perfEl.textContent = `帧: ${Math.round(1000/(t1-lastTime))} fps · 本帧 ${Math.round(t1-t0)} ms`;
        lastTime = t1;

        if (codes && codes.length) {
          const raw = codes[0].rawValue || '';
          const fmt = codes[0].format || 'unknown';
          if (raw && raw !== lastDetected) {
            lastDetected = raw;
            resultEl.textContent = raw;
            typeEl.textContent = '类型：' + fmt;
            // 蜂鸣/震动提示（可选）
            if (navigator.vibrate) navigator.vibrate(60);
          }
        }
      } catch(e) {
        // 忽略偶发错误
      }
    }
    scanLoopTimer = requestAnimationFrame(loop);
  };
  scanLoopTimer = requestAnimationFrame(loop);
  return true;
}

/* ---- ZXing Fallback ---- */
async function startZXing() {
  usingNative = false;
  modeBadge.textContent = 'ZXing 识别（fallback）';
  if (!window.ZXing) {
    await import('https://unpkg.com/@zxing/library@0.20.0/esm/index.js')
      .then(mod => window.ZXing = mod)
      .catch(()=>{});
  }
  if (!window.ZXing) throw new Error('ZXing 加载失败');

  const { BrowserMultiFormatReader, BarcodeFormat, DecodeHintType } = ZXing;
  const hints = new Map();
  hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.EAN_13, BarcodeFormat.EAN_8,
    BarcodeFormat.UPC_A,  BarcodeFormat.UPC_E,
    BarcodeFormat.CODE_128, BarcodeFormat.CODE_39,
    BarcodeFormat.CODE_93, BarcodeFormat.ITF,
    BarcodeFormat.CODABAR, BarcodeFormat.QR_CODE
  ]);
  zxingReader = new BrowserMultiFormatReader(hints, 500);

  // 选择设备ID
  const devId = cameraSelect.value || undefined;
  zxingActive = true;

  // 用 decodeFromVideoDevice 挂载到 <video>
  zxingReader.decodeFromVideoDevice(devId || null, video, (res, err) => {
    if (!zxingActive) return;
    if (res) {
      const raw = res.getText();
      const fmt = res.getBarcodeFormat() || 'UNKNOWN';
      if (raw && raw !== lastDetected) {
        lastDetected = raw;
        resultEl.textContent = raw;
        typeEl.textContent = '类型：' + fmt;
        if (navigator.vibrate) navigator.vibrate(60);
      }
    }
    // err 为 NotFound 等时可忽略，意味着本帧未解出
  });
}

/* ---- 启动扫描 ---- */
async function startScan() {
  btnStart.disabled = true; btnStop.disabled = false;
  try {
    await listCameras();
    // 首次尽量选后置（标签里含 back/environment）
    let chosen = '';
    for (const opt of cameraSelect.options) {
      const label = (opt.textContent||'').toLowerCase();
      if (label.includes('back') || label.includes('后') || label.includes('environment')) { chosen = opt.value; break; }
    }
    if (chosen) cameraSelect.value = chosen;

    await openCamera({ deviceId: cameraSelect.value || null, facingMode: lastFacingMode });

    // 优先使用原生
    const ok = await startNativeLoop();
    if (!ok) { await startZXing(); modeBadge.textContent = 'ZXing 识别（fallback）'; }
    else { modeBadge.textContent = '原生识别（BarcodeDetector）'; }
  } catch(e) {
    modeBadge.textContent = '启动失败';
    alert('无法启动摄像头或识别：' + (e.message||e));
    btnStart.disabled = false; btnStop.disabled = true;
  }
}

/* ---- 停止扫描 ---- */
function stopScan() {
  btnStart.disabled = false; btnStop.disabled = true;
  if (scanLoopTimer) cancelAnimationFrame(scanLoopTimer), scanLoopTimer = null;
  if (zxingReader && zxingActive) { zxingActive = false; zxingReader.reset(); }
  if (currentStream) { currentStream.getTracks().forEach(t=>t.stop()); currentStream = null; }
  btnTorch.disabled = true;
}

/* ---- 事件 ---- */
btnStart.addEventListener('click', startScan);
btnStop.addEventListener('click', stopScan);
btnTorch.addEventListener('click', toggleTorch);
btnFlip.addEventListener('click', async ()=>{
  // 切换前/后摄：若在 ZXing 模式，优先换设备；原生模式则切换 facingMode 再重启
  const i = cameraSelect.selectedIndex;
  if (cameraSelect.options.length >= 2) {
    cameraSelect.selectedIndex = (i+1) % cameraSelect.options.length;
  }
  stopScan();
  await startScan();
});

cameraSelect.addEventListener('change', async ()=>{
  // 切换具体设备
  if (!btnStop.disabled) { // 正在运行
    stopScan();
    await startScan();
  }
});

/* ---- 初始化 ---- */
(async ()=>{
  if (!('mediaDevices' in navigator)) {
    alert('此浏览器不支持摄像头 API');
    return;
  }
  await listCameras();
  modeBadge.textContent = ('BarcodeDetector' in window) ? '将使用原生识别' : '将使用 ZXing 回退';
})();
</script>
</body>
</html>
