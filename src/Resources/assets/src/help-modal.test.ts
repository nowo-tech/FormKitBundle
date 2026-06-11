import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createModalElement, hideModal, parseHelpModalData, showModal } from './help-modal';

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

async function importModuleFresh(): Promise<void> {
  vi.resetModules();
  await import('./help-modal.ts');
}

describe('help-modal', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    setDom('');
    // clean runtime globals used by the module
    delete (window as any).bootstrap;
    delete (window as any).$;
    delete (window as any).jQuery;
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
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

  it('exports parser and covers parse branches', () => {
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
        // close hook inside body so selector [data-help-modal-close] exists
        content: '<button data-help-modal-close="1">Close</button>',
      })}'></label>`,
    );

    await importModuleFresh();

    const iconBtn = (document.getElementById('label-d') as HTMLElement).querySelector(
      '.nowo-help-modal-trigger',
    ) as HTMLElement;
    iconBtn.click();
    expect(show).toHaveBeenCalled();

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

    // tailwind
    ((document.getElementById('label-f') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeTw = document.querySelector('#hm-tw [data-help-modal-close="1"]') as HTMLElement;
    closeTw.click();
    const modalTw = document.getElementById('hm-tw') as HTMLElement;
    expect(modalTw.style.display).toBe('none');

    // foundation fallback
    ((document.getElementById('label-g') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeFd = document.querySelector('#hm-fd [data-help-modal-close="1"]') as HTMLElement;
    closeFd.click();
    const modalFd = document.getElementById('hm-fd') as HTMLElement;
    expect(modalFd.style.display).toBe('none');
  });

  it('showModal handles existing modal and fallback bootstrap without api', () => {
    const existing = document.createElement('div');
    existing.id = 'hm-existing';
    document.body.appendChild(existing);

    showModal({
      id: 'hm-existing',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(document.getElementById('hm-existing')).toBe(existing);

    showModal({
      id: 'hm-b5-fallback',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'T',
      content: '',
    });
    const created = document.getElementById('hm-b5-fallback') as HTMLElement;
    expect(created).toBeTruthy();
    expect(created.style.display).toBe('');
  });

  it('hideModal no-op when target element does not exist', () => {
    hideModal({
      id: 'missing-id',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(document.getElementById('missing-id')).toBeNull();
  });

  it('uses DOM shell template when Twig template is present', () => {
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
  });

  it('createModalElement covers bootstrap and fallback constructors', () => {
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

  it('showModal logs at info level when opening', () => {
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
  });
});

