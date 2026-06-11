(function(){"use strict";const p={script:"color:#0ea5e9;font-weight:bold",debug:"color:#6b7280",info:"color:#2563eb",warn:"color:#d97706",error:"color:#dc2626;font-weight:bold"},b={script:"📦",debug:"🔍",info:"ℹ️",warn:"⚠️",error:"❌"};function L(e){return e.map(t=>typeof t=="object"&&t!==null&&!(t instanceof Error)?JSON.stringify(t):t)}function E(e,t){if(t!==void 0&&t!==""){console.log(`%c${b.script} ${e} script loaded, build time: %c${t}`,p.script,"color:#059669");return}console.log(`%c${b.script} ${e} script loaded`,p.script)}function x(e,t,o){const l=`%c${b[e]} ${t}`,n=p[e],r=console[e];if(o.length>0){r(l,n,...L(o));return}r(l,n)}function s(e,t,o){return(...l)=>{e&&x(o,t,l)}}function i(){}let g=null;function M(e){g=e}function a(){return g!==null?g:{scriptLoaded:i,setDebug:i,debug:i,info:i,warn:i,error:i}}function A(e,t={}){const o=`[${e}]`,{buildTime:l,alwaysLog:n=!1}=t,r=n===!0;return{scriptLoaded(){E(o,l)},setDebug(f){},debug:s(r,o,"debug"),info:s(r,o,"info"),warn:s(r,o,"warn"),error:s(r,o,"error")}}const S=globalThis.__FORM_KIT_HELP_MODAL_BUILD_TIME__,h=A("form-kit-help-modal",{buildTime:S,alwaysLog:!0});h.scriptLoaded(),M(h);const T="[data-nowo-help-modal-title]",_="[data-nowo-help-modal-body]",H={bootstrap5:"nowo-formkit-help-modal-shell-bootstrap5",bootstrap4:"nowo-formkit-help-modal-shell-bootstrap4",tailwind:"nowo-formkit-help-modal-shell-tailwind",foundation:"nowo-formkit-help-modal-shell-foundation"};function I(e){var n;const t=H[e],o=document.getElementById(t),l=(n=o==null?void 0:o.content)==null?void 0:n.firstElementChild;return l?l.cloneNode(!0):null}function c(e,t){const o=e.querySelector(T);o&&(t.title_html?o.innerHTML=t.title_html:o.textContent=t.title??"")}function u(e,t){const o=e.querySelector(_);o&&(o.innerHTML=t)}function C(e){try{const t=JSON.parse(e);return!t||typeof t.id!="string"?(a().warn("Invalid help modal payload (missing id)",t),null):t}catch(t){return a().warn("Cannot parse help modal payload",{raw:e,error:t}),null}}function O(){return`
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    `}function D(){return`
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    `}function $(){return`
      <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" data-help-modal-close="1"></div>
        <div class="relative z-10 w-full max-w-lg mx-4 rounded-lg bg-white dark:bg-gray-900 shadow-lg">
          <div class="flex items-start justify-between p-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-left">
              <h3 class="text-lg font-semibold" data-nowo-help-modal-title></h3>
            </div>
            <button type="button" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800" data-help-modal-close="1" aria-label="Close">✕</button>
          </div>
          <div class="p-4" style="max-height:70vh; overflow:auto;" data-nowo-help-modal-body></div>
        </div>
      </div>
    `}function B(){return`
    <div class="nowo-help-modal-overlay fixed inset-0 z-50" style="background: rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center;">
      <div class="nowo-help-modal-panel rounded shadow" style="background:#fff; max-width: 640px; width: calc(100% - 32px);">
        <div style="padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.1); display:flex; justify-content:space-between; align-items:center;">
          <div data-nowo-help-modal-title style="flex:1;"></div>
          <button type="button" aria-label="Close" data-help-modal-close="1" style="cursor:pointer; border:0; background:transparent; font-size:18px; line-height:1;">✕</button>
        </div>
        <div style="padding:12px 16px; max-height:70vh; overflow:auto;" data-nowo-help-modal-body></div>
      </div>
    </div>
  `}function m(e,t,o){const l=I(t);if(l){e.appendChild(l);return}e.innerHTML=o}function N(e){const t=e.framework||"bootstrap5",o=e.content||"";if(t==="bootstrap5"){const n=document.createElement("div");return n.id=e.id,n.className="modal fade",n.setAttribute("tabindex","-1"),n.setAttribute("aria-hidden","true"),m(n,"bootstrap5",O()),u(n,o),c(n,e),n}if(t==="bootstrap4"){const n=document.createElement("div");return n.id=e.id,n.className="modal fade",n.setAttribute("tabindex","-1"),n.setAttribute("role","dialog"),n.setAttribute("aria-hidden","true"),m(n,"bootstrap4",D()),u(n,o),c(n,e),n}if(t==="tailwind"){const n=document.createElement("div");return n.id=e.id,n.className="nowo-help-modal-tailwind",n.setAttribute("role","dialog"),n.setAttribute("aria-modal","true"),m(n,"tailwind",$()),u(n,o),c(n,e),n}const l=document.createElement("div");return l.id=e.id,l.className="nowo-help-modal-foundation",l.setAttribute("role","dialog"),l.setAttribute("aria-modal","true"),m(l,"foundation",B()),u(l,o),c(l,e),l}function w(e){var n,r;a().info("Help modal opened",{id:e.id,framework:e.framework});const t=document.getElementById(e.id),o=t??N(e);if(t||document.body.appendChild(o),o.querySelectorAll("[data-help-modal-close]").forEach(f=>{f.addEventListener("click",()=>{j(e)})}),e.framework==="bootstrap5"&&((n=window.bootstrap)!=null&&n.Modal)){window.bootstrap.Modal.getOrCreateInstance(o).show();return}if(e.framework==="bootstrap4"&&window.$&&typeof((r=window.$.fn)==null?void 0:r.modal)=="function"){window.$(o).modal("show");return}o.style.display=""}function j(e){var o,l;a().debug("hideModal",{id:e.id,framework:e.framework});const t=document.getElementById(e.id);if(t){if(e.framework==="bootstrap5"&&((o=window.bootstrap)!=null&&o.Modal)){window.bootstrap.Modal.getOrCreateInstance(t).hide();return}if(e.framework==="bootstrap4"&&window.$&&typeof((l=window.$.fn)==null?void 0:l.modal)=="function"){window.$(t).modal("hide");return}t.style.display="none"}}function q(){return"nowo-help-modal-trigger nowo-help-modal-trigger--circle"}function y(){a().debug("initHelpModal start");const e=Array.from(document.querySelectorAll("label[data-nowo-help-modal]"));a().debug("labels detected",{count:e.length}),e.forEach(t=>{var v,k;const o=t.getAttribute("data-nowo-help-modal");if(!o)return;const l=C(o);if(!l||t.querySelector(".nowo-help-modal-trigger"))return;const r=document.createElement("span");r.className=((v=l.trigger_class)==null?void 0:v.trim())||q(),r.setAttribute("role","button"),r.setAttribute("tabindex","0"),r.setAttribute("aria-label",((k=l.aria_label)==null?void 0:k.trim())||"Help"),r.innerHTML=l.icon_html,t.appendChild(r);const f=d=>{d.preventDefault(),d.stopPropagation(),w(l)};r.addEventListener("click",f),r.addEventListener("keydown",d=>{(d.key==="Enter"||d.key===" ")&&(d.preventDefault(),w(l))}),a().debug("help modal trigger attached",{id:l.id})})}function F(){document.readyState==="loading"?(a().debug("DOM loading: wait DOMContentLoaded"),document.addEventListener("DOMContentLoaded",y)):(a().debug("DOM ready: init immediately"),y())}F()})();
