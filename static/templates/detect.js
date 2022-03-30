async function try_navigator_userAgentData() {
  let uach = null;
  if (navigator.userAgentData) {
    // this browser supports userAgentData
    try {
      uach = await navigator.userAgentData.getHighEntropyValues(['architecture', 'bitness', 'platform', 'mobile']);
    }
    catch (e) {
      // this can happen if the user-agent refuses to return one of the hints
      uach = {};
    }

    // fill in platform if we need to (do we ever?) 
    if (!uach.platform) {
      uach.platform = navigator.userAgentData.platform;
    }

    // fill in mobile if we need to (do we ever?)
    if (uach.mobile === undefined) {
      uach.mobile = navigator.userAgentData.mobile;
    }
  }
  return uach;
}

function has_m1_gpu() {
  let gl = null;
  const canvas = document.createElement("canvas");
  try {
    gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
  }
  catch (e) {
    return false;
  }

  if (!gl) {
    return false;
  }

  const d = gl.getExtension('WEBGL_debug_renderer_info');
  const r = gl.getParameter(d.UNMASKED_RENDERER_WEBGL);
  return r === 'Apple M1';
}

const PLATFORM_MACOS = 'macOS';
const PLATFORM_WINDOWS = 'Windows';
const PLATFORM_LINUX = 'Linux';

const ARCH_X86 = 'x86';
const ARCH_ARM = 'arm';

const BITS_32 = '32';
const BITS_64 = '64';

async function get_userAgentData() {
  // get userAgentData if available
  const uach = await try_navigator_userAgentData() || {};

  // try to fill in userAgentData if anything we need is unknown
  if (!uach.platform || !uach.architecture || !uach.bitness) {
    const parser = new UAParser();

    if (!uach.platform) {
      const os = parser.getOS();

      if (os.name === 'Mac OS') {
        uach.platform = PLATFORM_MACOS;
      }
      else if (os.name === 'Windows') {
        uach.platform = PLATFORM_WINDOWS;
      }
      else if (parser.getUA().includes('Linux')) {
        // getOS() returns distro names, not Linux
        uach.platform = PLATFORM_LINUX;
      }
    }

    if (!uach.architecture || !uach.bitness) {
      if (uach.platform === PLATFORM_MACOS) {
        // getCPU() cannot distinguish x86 from ARM Macs 
        if (has_m1_gpu()) {
          uach.architecture = ARCH_ARM;
          uach.bitness = BITS_64;
        }
        else {
          uach.architecture = ARCH_X86;
          uach.bitness = BITS_64;
        }
      }
      else {
        const arch = parser.getCPU().architecture;

        if (arch === 'amd64') {
          uach.architecture = ARCH_X86;
          uach.bitness = BITS_64;
        }
        else if (arch === 'arm64') {
          uach.architecture = ARCH_ARM;
          uach.bitness = BITS_64;
        }
      }
    }

    if (uach.mobile === undefined) {
      uach.mobile = parser.getDevice().type === 'mobile';
    }
  }

  return uach;
}

document.addEventListener("DOMContentLoaded", async () => {

  // get what data we can
  const uach = await get_userAgentData();

  const base_url = 'https://github.com/vassalengine/vassal/releases';

  const ver = '{{ current_version }}';
  const dl_url = `${base_url}/download/${ver}`;

  const get_vassal = 'Get Vassal';
  let btn_text = get_vassal;
  let btn_link = '/download.html';

  let specific_download = false;

  if (!uach.mobile) {
    if (uach.platform === PLATFORM_WINDOWS) {
      if (uach.architecture === ARCH_X86) {
        if (uach.bitness === BITS_64) {
          specific_download = true;
          btn_text = `${get_vassal} for ${uach.platform} (64-bit x86)`;
          btn_link = `${dl_url}/VASSAL-${ver}-windows-x86_64.exe`;
        }
        else if (uach.bitness === BITS_32) {
          specific_download = true;
          btn_text = `${get_vassal} for ${uach.platform} (32-bit x86)`;
          btn_link = `${dl_url}/VASSAL-${ver}-windows-x86_32.exe`;
        }
      }
      else if (uach.architecture === ARCH_ARM && uach.bitness === BITS_64) {
        specific_download = true;
        btn_text = `${get_vassal} for ${uach.platform} (64-bit ARM)`;
        btn_link = `${dl_url}/VASSAL-${ver}-windows-aarch64.exe`;
      }
    }
    else if (uach.platform === PLATFORM_MACOS && uach.bitness === BITS_64) {
      if (uach.architecture === ARCH_X86) {
        specific_download = true;
        btn_text = `${get_vassal} for ${uach.platform} (Intel)`;
        btn_link = `${dl_url}/VASSAL-${ver}-macos-x86_64.dmg`;
      }
      else if (uach.architecture === ARCH_ARM) {
        specific_download = true;
        btn_text = `${get_vassal} for ${uach.platform} (Apple Silicon)`;
        btn_link = `${dl_url}/VASSAL-${ver}-macos-aarch64.dmg`;
      }
    }
    else if (uach.platform === PLATFORM_LINUX) {
      specific_download = true;
      btn_text = `${get_vassal} for ${uach.platform}`;
      btn_link = `${dl_url}/VASSAL-${ver}-linux.tar.bz2`;
    }
  }

  if (!specific_download) {
    // detection failed to be specific enough
    const alt_downloads = document.getElementById('alt_downloads');
    alt_downloads.style.display = 'none';
  }

  const btn = document.getElementById('download_btn');
  btn.textContent = btn_text; 
  btn.href = btn_link;
});
