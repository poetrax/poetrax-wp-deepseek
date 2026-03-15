'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const userAgent = navigator.userAgent.toLowerCase();
    if (userAgent.indexOf("iphone;") !== -1)
{
    // iPhone
}
else if (userAgent.indexOf("ipad;") !== -1 || userAgent.indexOf("macintosh;") !== -1)
{
    // iPad
}
else
{
    // Think Different ;)
}
    
const sound = new Howl({
  src: ['https://poetrax.ru//wp-content/uploads/2025/06/0_aleksandr-blok_aviator_hevi-metall_1.mp3'],
  html5: true,
  onplay: () => initWaveform()
});

let wave;
function initWaveform() {
  const canvas = document.createElement('canvas');
  document.getElementById('waveform').appendChild(canvas);
  
  wave = new Wave();
  wave.fromElement('audio', 'canvas', {
    type: 'flower', 
    colors: ['#ff0000', '#00ff00'] 
  });
}

    document.getElementById('playBtn').addEventListener('click', () => {
      sound.play();
    });

    document.getElementById('stopBtn').addEventListener('click', () => {
      sound.stop();
      document.getElementById('waveform').innerHTML = ''; // Очищаем визуализацию
    });

});
