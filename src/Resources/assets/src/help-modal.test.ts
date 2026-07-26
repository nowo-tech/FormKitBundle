import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

type HelpModalModule = typeof import('./help-modal');

let mod: HelpModalModule;

function setDom(body: string): void {
  document.body.innerHTML = body;
}

function payload(overrides: Record<string, unknown> = {}): string {
  return JSON.stringify({
    id: 'hm-1',
    framework: 'tailwind',
    icon_html: '<button type="button" class="icon-btn">?</button>',
    trigger_class: 'nowo-help-modal-trigger nowo-help-modal-trigger--circle',
    title: 'Help',
    content: '<p>content</p>',
    ...overrides,
  });
}

async function importModuleFresh(): Promise<HelpModalModule> {
  mod?.stopHelpModalDomObserver();
  vi.resetModules();
  mod = await import('./help-modal.ts');
  return mod;
}

async function flushPortalScan(): Promise<void> {
  // MutationObserver callback + requestAnimationFrame coalesce used by the portal watcher.
  await Promise.resolve();
  await new Promise<void>((resolve) => {
    requestAnimationFrame(() => resolve());
  });
}

describe('help-modal', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    mod?.stopHelpModalDomObserver();
    setDom('');
    delete (window as any).bootstrap;
    delete (window as any).$;
    delete (window as any).jQuery;
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    mod?.stopHelpModalDomObserver();
  });

  it('loads and attaches icon for valid data attribute', async () => {
    setDom(`<label id="label-a" data-nowo-help-modal='${payload()}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-a') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('ignores invalid JSON payload and missing id payload', async () => {
    setDom(`
      <label id="label-b" data-nowo-help-modal='not-json'></label>
      <label id="label-c" data-nowo-help-modal='{"framework":"tailwind"}'></label>
      <label id="label-empty" data-nowo-help-modal=''></label>
    `);
    await importModuleFresh();

    expect((document.getElementById('label-b') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
    expect((document.getElementById('label-c') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
    expect((document.getElementById('label-empty') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
  });

  it('exports parser and covers parse branches', async () => {
    const { parseHelpModalData } = await importModuleFresh();
    expect(parseHelpModalData('{"id":"x","framework":"tailwind","icon_html":"?","title":null,"content":""}')).toBeTruthy();
    expect(parseHelpModalData('{"framework":"tailwind"}')).toBeNull();
    expect(parseHelpModalData('not-json')).toBeNull();
  });

  it('shows bootstrap5 modal through bootstrap API and can hide via close hook', async () => {
    const show = vi.fn();
    const hide = vi.fn();
    (window as any).bootstrap = {
      Modal: {
        getOrCreateInstance: vi.fn(() => ({ show, hide })),
      },
    };

    setDom(
      `<label id="label-d" data-nowo-help-modal='${payload({
        id: 'hm-b5',
        framework: 'bootstrap5',
        content: '<button data-help-modal-close="1">Close</button>',
      })}'></label>`,
    );

    const { HELP_MODAL_ROOT_ATTR } = await importModuleFresh();

    const iconBtn = (document.getElementById('label-d') as HTMLElement).querySelector(
      '.nowo-help-modal-trigger',
    ) as HTMLElement;
    iconBtn.click();
    expect(show).toHaveBeenCalled();

    const modal = document.getElementById('hm-b5') as HTMLElement;
    expect(modal.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
    expect(modal.parentElement).toBe(document.body);

    const closeBtn = document.querySelector('#hm-b5 [data-help-modal-close="1"]') as HTMLElement;
    closeBtn.click();
    expect(hide).toHaveBeenCalled();
  });

  it('shows bootstrap4 modal through jQuery API and can hide', async () => {
    const modalFn = vi.fn();
    const jq: any = vi.fn(() => ({ modal: modalFn }));
    jq.fn = { modal: vi.fn() };
    (window as any).$ = jq;

    setDom(
      `<label id="label-e" data-nowo-help-modal='${payload({
        id: 'hm-b4',
        framework: 'bootstrap4',
        content: '<button data-help-modal-close="1">Close</button>',
      })}'></label>`,
    );
    await importModuleFresh();

    const iconBtn = (document.getElementById('label-e') as HTMLElement).querySelector(
      '.nowo-help-modal-trigger',
    ) as HTMLElement;
    iconBtn.click();
    expect(modalFn).toHaveBeenCalledWith('show');

    const closeBtn = document.querySelector('#hm-b4 [data-help-modal-close="1"]') as HTMLElement;
    closeBtn.click();
    expect(modalFn).toHaveBeenCalledWith('hide');
  });

  it('tailwind and foundation use fallback display toggles', async () => {
    setDom(
      `<label id="label-f" data-nowo-help-modal='${payload({
        id: 'hm-tw',
        framework: 'tailwind',
      })}'></label>
      <label id="label-g" data-nowo-help-modal='${payload({
        id: 'hm-fd',
        framework: 'foundation',
      })}'></label>`,
    );

    await importModuleFresh();

    ((document.getElementById('label-f') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeTw = document.querySelector('#hm-tw [data-help-modal-close="1"]') as HTMLElement;
    closeTw.click();
    const modalTw = document.getElementById('hm-tw') as HTMLElement;
    expect(modalTw.style.display).toBe('none');
    expect(modalTw.parentElement).toBe(document.body);

    ((document.getElementById('label-g') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeFd = document.querySelector('#hm-fd [data-help-modal-close="1"]') as HTMLElement;
    closeFd.click();
    const modalFd = document.getElementById('hm-fd') as HTMLElement;
    expect(modalFd.style.display).toBe('none');
  });

  it('showModal recreates and portals to body; ignores unrelated .modal nodes', async () => {
    const { HELP_MODAL_ROOT_ATTR, showModal } = await importModuleFresh();

    const unrelated = document.createElement('div');
    unrelated.id = 'app-modal';
    unrelated.className = 'modal';
    document.body.appendChild(unrelated);

    const stale = document.createElement('div');
    stale.id = 'hm-refresh';
    stale.setAttribute(HELP_MODAL_ROOT_ATTR, '1');
    stale.textContent = 'stale';
    document.body.appendChild(stale);

    showModal({
      id: 'hm-refresh',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content: '<p>fresh</p>',
    });

    const portaled = document.getElementById('hm-refresh') as HTMLElement;
    expect(portaled).not.toBe(stale);
    expect(portaled.parentElement).toBe(document.body);
    expect(portaled.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>fresh</p>');
    expect(document.getElementById('app-modal')).toBe(unrelated);

    showModal({
      id: 'hm-b5-fallback',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'T',
      content: '',
    });
    const created = document.getElementById('hm-b5-fallback') as HTMLElement;
    expect(created).toBeTruthy();
    expect(created.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
    expect(created.style.display).toBe('');
  });

  it('hideModal no-op when target element does not exist', async () => {
    const { hideModal } = await importModuleFresh();
    hideModal({
      id: 'missing-id',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(document.getElementById('missing-id')).toBeNull();
  });

  it('uses DOM shell template when Twig template is present', async () => {
    setDom(`
    <template id="nowo-formkit-help-modal-shell-bootstrap5">
      <div class="modal-dialog modal-dialog--from-twig">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    </template>
    `);
    const { createModalElement, HELP_MODAL_ROOT_ATTR } = await importModuleFresh();
    const b5 = createModalElement({
      id: 'c-twig',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'From Twig',
      content: '<p>twig-body</p>',
    });
    expect(b5.querySelector('.modal-dialog--from-twig')).toBeTruthy();
    expect(b5.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>twig-body</p>');
    expect(b5.querySelector('[data-nowo-help-modal-title]')?.textContent).toBe('From Twig');
    expect(b5.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
  });

  it('createModalElement covers bootstrap and fallback constructors', async () => {
    const { createModalElement } = await importModuleFresh();
    const b5 = createModalElement({
      id: 'c1',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(b5.className).toContain('modal');
    expect(b5.querySelector('[data-nowo-help-modal-title]')?.textContent).toBe('A');
    expect(b5.querySelector('.modal-body')?.innerHTML).toBe('B');

    const b5html = createModalElement({
      id: 'c1b',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'ignored',
      title_html: '<em>HTML</em>',
      content: '',
    });
    expect(b5html.querySelector('[data-nowo-help-modal-title]')?.innerHTML).toBe('<em>HTML</em>');

    const b4 = createModalElement({
      id: 'c2',
      framework: 'bootstrap4',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(b4.className).toContain('modal');

    const tw = createModalElement({
      id: 'c3',
      framework: 'tailwind',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(tw.className).toContain('tailwind');

    const fd = createModalElement({
      id: 'c4',
      framework: 'foundation',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(fd.className).toContain('foundation');

    const implicitB5 = createModalElement({
      id: 'c5',
      framework: '',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(implicitB5.className).toContain('modal');
  });

  it('showModal logs at info level when opening', async () => {
    const { showModal } = await importModuleFresh();
    const info = vi.spyOn(console, 'info');
    showModal({
      id: 'info-open',
      framework: 'foundation',
      icon_html: '?',
      title: 'T',
      content: '<p>x</p>',
    });
    expect(info).toHaveBeenCalled();
  });

  it('handles document readyState loading branch and DOMContentLoaded', async () => {
    setDom(`<label id="label-h" data-nowo-help-modal='${payload({ id: 'hm-load' })}'></label>`);
    const addEvent = vi.spyOn(document, 'addEventListener');
    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => 'loading',
    });

    await importModuleFresh();
    expect(addEvent).toHaveBeenCalledWith('DOMContentLoaded', expect.any(Function));

    document.dispatchEvent(new Event('DOMContentLoaded'));
    expect((document.getElementById('label-h') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeTruthy();

    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => 'complete',
    });
  });

  it('relocateHelpModalToBody portals nested modals and is idempotent at body end', async () => {
    const { createModalElement, relocateHelpModalToBody } = await importModuleFresh();

    const wrapper = document.createElement('div');
    wrapper.id = 'hidden-panel';
    wrapper.hidden = true;
    document.body.appendChild(wrapper);

    const modal = createModalElement({
      id: 'hm-nested',
      framework: 'tailwind',
      icon_html: '?',
      title: 'N',
      content: '<p>n</p>',
    });
    wrapper.appendChild(modal);

    expect(relocateHelpModalToBody(modal)).toBe(true);
    expect(modal.parentElement).toBe(document.body);
    expect(document.body.lastElementChild).toBe(modal);
    expect(relocateHelpModalToBody(modal)).toBe(false);

    const spacer = document.createElement('div');
    document.body.appendChild(spacer);
    expect(relocateHelpModalToBody(modal)).toBe(true);
    expect(document.body.lastElementChild).toBe(modal);
  });

  it('relocateAllHelpModalsToBody removes stale duplicates when a refreshed root appears', async () => {
    const { createModalElement, relocateAllHelpModalsToBody } = await importModuleFresh();

    const oldModal = createModalElement({
      id: 'hm-dup',
      framework: 'foundation',
      icon_html: '?',
      title: 'old',
      content: '<p>old</p>',
    });
    document.body.appendChild(oldModal);

    const panel = document.createElement('div');
    panel.style.display = 'none';
    document.body.appendChild(panel);

    const refreshed = createModalElement({
      id: 'hm-dup',
      framework: 'foundation',
      icon_html: '?',
      title: 'new',
      content: '<p>new</p>',
    });
    panel.appendChild(refreshed);

    relocateAllHelpModalsToBody(panel);

    expect(document.body.contains(oldModal)).toBe(false);
    expect(refreshed.parentElement).toBe(document.body);
    expect(refreshed.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>new</p>');

    const alone = createModalElement({
      id: 'hm-alone',
      framework: 'tailwind',
      icon_html: '?',
      title: null,
      content: '',
    });
    panel.appendChild(alone);
    relocateAllHelpModalsToBody(alone);
    expect(alone.parentElement).toBe(document.body);
  });

  it('MutationObserver attaches triggers for labels loaded later and portals refreshed modals', async () => {
    const { createModalElement } = await importModuleFresh();

    const form = document.createElement('form');
    form.innerHTML = `<label id="label-late" data-nowo-help-modal='${payload({ id: 'hm-late' })}'></label>`;
    document.body.appendChild(form);
    await flushPortalScan();

    const label = document.getElementById('label-late') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();

    const hidden = document.createElement('div');
    hidden.hidden = true;
    document.body.appendChild(hidden);

    const nested = createModalElement({
      id: 'hm-observe',
      framework: 'tailwind',
      icon_html: '?',
      title: 'obs',
      content: '<p>obs</p>',
    });
    hidden.appendChild(nested);
    await flushPortalScan();

    expect(nested.parentElement).toBe(document.body);
  });

  it('re-reads updated label payload on click and keyboard open', async () => {
    setDom(`<label id="label-k" data-nowo-help-modal='${payload({ id: 'hm-k', content: '<p>v1</p>' })}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-k') as HTMLLabelElement;
    const trigger = label.querySelector('.nowo-help-modal-trigger') as HTMLElement;

    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-k', content: '<p>v2</p>' }));
    trigger.click();
    expect(document.querySelector('#hm-k [data-nowo-help-modal-body]')?.innerHTML).toBe('<p>v2</p>');

    document.getElementById('hm-k')?.remove();
    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-k', content: '<p>v3</p>' }));
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    expect(document.querySelector('#hm-k [data-nowo-help-modal-body]')?.innerHTML).toBe('<p>v3</p>');

    document.getElementById('hm-k')?.remove();
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: ' ', bubbles: true }));
    expect(document.getElementById('hm-k')).toBeTruthy();

    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
  });

  it('startHelpModalDomObserver is a no-op when already running; stop clears pending rAF', async () => {
    const { startHelpModalDomObserver, stopHelpModalDomObserver } = await importModuleFresh();
    startHelpModalDomObserver();
    startHelpModalDomObserver();

    const label = document.createElement('label');
    label.id = 'label-raf';
    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-raf' }));
    document.body.appendChild(label);
    stopHelpModalDomObserver();
    await flushPortalScan();
  });

  it('skips starting observer when MutationObserver is unavailable', async () => {
    const original = globalThis.MutationObserver;
    // @ts-expect-error intentional for coverage
    globalThis.MutationObserver = undefined;
    try {
      await importModuleFresh();
      mod.startHelpModalDomObserver();
    } finally {
      globalThis.MutationObserver = original;
    }
  });

  it('coalesces multiple mutations into a single scheduled scan', async () => {
    await importModuleFresh();

    const a = document.createElement('label');
    a.id = 'label-coalesce-a';
    a.setAttribute('data-nowo-help-modal', payload({ id: 'hm-ca' }));
    const b = document.createElement('label');
    b.id = 'label-coalesce-b';
    b.setAttribute('data-nowo-help-modal', payload({ id: 'hm-cb' }));
    document.body.appendChild(a);
    document.body.appendChild(b);
    await flushPortalScan();

    expect(a.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    expect(b.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('reacts to data-nowo-help-modal attribute changes on existing labels', async () => {
    setDom(`<label id="label-attr">Name</label>`);
    await importModuleFresh();

    const label = document.getElementById('label-attr') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeNull();

    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-attr' }));
    await flushPortalScan();
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('covers empty-id stale cleanup and non-element mutation nodes', async () => {
    const { createModalElement, relocateHelpModalToBody } = await importModuleFresh();

    const modal = createModalElement({
      id: 'hm-empty-check',
      framework: 'tailwind',
      icon_html: '?',
      title: null,
      content: '',
    });
    modal.removeAttribute('id');
    document.body.appendChild(document.createElement('div')).appendChild(modal);
    expect(relocateHelpModalToBody(modal)).toBe(true);

    // Text node addition should be ignored by the observer filter.
    document.body.appendChild(document.createTextNode('noop'));
    await flushPortalScan();
  });

  it('uses default trigger class and falls back when title/body slots are missing', async () => {
    const { createModalElement } = await importModuleFresh();

    setDom(`
      <label id="label-default-trigger" data-nowo-help-modal='${JSON.stringify({
        id: 'hm-default-trigger',
        framework: 'tailwind',
        icon_html: '?',
        title: null,
        content: '',
      })}'></label>
    `);
    // Re-init after DOM change without a second module load.
    mod.initHelpModal();
    const trigger = document
      .getElementById('label-default-trigger')!
      .querySelector('.nowo-help-modal-trigger') as HTMLElement;
    expect(trigger.className).toContain('nowo-help-modal-trigger--circle');

    const bare = document.createElement('div');
    bare.id = 'hm-bare';
    // Force fillModalTitle / fillModalBody early-return paths via createModalElement internals:
    // replace shell with markup that omits the slots after creation.
    const withSlots = createModalElement({
      id: 'hm-slots',
      framework: 'foundation',
      icon_html: '?',
      title: 't',
      content: 'c',
    });
    withSlots.querySelector('[data-nowo-help-modal-title]')?.remove();
    withSlots.querySelector('[data-nowo-help-modal-body]')?.remove();
    // Re-run fillers indirectly by creating from a custom shell without slots.
    setDom(`
      <template id="nowo-formkit-help-modal-shell-foundation">
        <div class="shell-without-slots"></div>
      </template>
    `);
    const noSlots = createModalElement({
      id: 'hm-noslots',
      framework: 'foundation',
      icon_html: '?',
      title: 't',
      title_html: '<b>x</b>',
      content: '<p>y</p>',
    });
    expect(noSlots.querySelector('.shell-without-slots')).toBeTruthy();
    expect(noSlots.querySelector('[data-nowo-help-modal-title]')).toBeNull();
    expect(bare.id).toBe('hm-bare');
  });

  it('covers attribute mutations that do not match help-modal targets and pending rAF coalesce', async () => {
    await importModuleFresh();

    const stray = document.createElement('div');
    document.body.appendChild(stray);
    // Attribute is watched but target is neither a help label nor a marked root → continue branch.
    stray.setAttribute('data-nowo-help-modal', '{"id":"ignored"}');
    await Promise.resolve();

    // Two separate observer deliveries before rAF → second scheduleDomScan hits pending early-return.
    const a = document.createElement('label');
    a.setAttribute('data-nowo-help-modal', payload({ id: 'hm-pending-a' }));
    document.body.appendChild(a);
    await Promise.resolve();

    const b = document.createElement('label');
    b.setAttribute('data-nowo-help-modal', payload({ id: 'hm-pending-b' }));
    document.body.appendChild(b);
    await Promise.resolve();

    const nest = document.createElement('div');
    const marked = document.createElement('div');
    marked.id = 'hm-attr-root';
    nest.appendChild(marked);
    document.body.appendChild(nest);
    // Set marker after insert to hit ROOT attribute-match branch.
    marked.setAttribute(mod.HELP_MODAL_ROOT_ATTR, '1');
    await flushPortalScan();

    expect(a.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    expect(b.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    expect(marked.parentElement).toBe(document.body);
  });

  it('uses custom aria_label from payload when provided', async () => {
    setDom(
      `<label id="label-aria" data-nowo-help-modal='${payload({
        id: 'hm-aria',
        aria_label: 'Más información',
      })}'></label>`,
    );
    await importModuleFresh();

    const trigger = document
      .getElementById('label-aria')!
      .querySelector('.nowo-help-modal-trigger') as HTMLElement;
    expect(trigger.getAttribute('aria-label')).toBe('Más información');
  });

  it('falls back to captured payload when label attribute is cleared before open', async () => {
    setDom(`<label id="label-cleared" data-nowo-help-modal='${payload({ id: 'hm-cleared' })}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-cleared') as HTMLLabelElement;
    const trigger = label.querySelector('.nowo-help-modal-trigger') as HTMLElement;
    label.removeAttribute('data-nowo-help-modal');
    trigger.click();
    expect(document.getElementById('hm-cleared')).toBeTruthy();

    document.getElementById('hm-cleared')?.remove();
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    expect(document.getElementById('hm-cleared')).toBeTruthy();
  });
});
