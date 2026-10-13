import { Application } from '@hotwired/stimulus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ComboboxController, { DEFAULT_REQUIRED_MESSAGE } from './combobox_controller';

const tick = (): Promise<void> => new Promise((r) => setTimeout(r, 0));

const markup = (rootAttrs = '', queryAttrs = '', value = '', withEmpty = true): string => `
  <form data-controller="combobox" data-action="submit->combobox#requireValue" ${rootAttrs}>
    <input data-combobox-target="query" ${queryAttrs}
      data-action="input->combobox#filter focus->combobox#onQueryFocus keydown->combobox#onQueryKeydown" />
    <input type="hidden" data-combobox-target="value" value="${value}" />
    <ul data-combobox-target="list" hidden>
      <li role="presentation"><button type="button" data-combobox-target="option" data-action="combobox#select"
        data-search="alpha one" data-value="1" data-label="Alpha">Alpha</button></li>
      <li role="presentation"><button type="button" data-combobox-target="option" data-action="combobox#select"
        data-search="beta two" data-value="2" data-label="Beta">Beta</button></li>
      <li role="presentation"><button type="button" data-combobox-target="option" data-action="combobox#select"
        data-value="3">  Gamma  </button></li>
      <li role="presentation"><button type="button" data-combobox-target="option" data-action="combobox#select"
        data-label="No value">No value</button></li>
      ${withEmpty ? '<li data-combobox-target="empty" hidden>No matches</li>' : ''}
    </ul>
    <button type="submit">Go</button>
  </form>
  <p id="outside">outside</p>
`;

let app: Application;

const mount = async (html: string): Promise<ComboboxController> => {
  document.body.innerHTML = html;
  app = Application.start();
  app.register('combobox', ComboboxController);
  await tick();
  const el = document.querySelector('[data-controller="combobox"]') as HTMLElement;

  return app.getControllerForElementAndIdentifier(el, 'combobox') as ComboboxController;
};

const q = (): HTMLInputElement => document.querySelector('[data-combobox-target="query"]') as HTMLInputElement;
const v = (): HTMLInputElement => document.querySelector('[data-combobox-target="value"]') as HTMLInputElement;
const list = (): HTMLElement => document.querySelector('[data-combobox-target="list"]') as HTMLElement;
const options = (): HTMLElement[] => Array.from(document.querySelectorAll<HTMLElement>('[data-combobox-target="option"]'));
const empty = (): HTMLElement => document.querySelector('[data-combobox-target="empty"]') as HTMLElement;

const key = (k: string, init: KeyboardEventInit = {}): KeyboardEvent => {
  const event = new KeyboardEvent('keydown', { key: k, bubbles: true, cancelable: true, ...init });
  q().dispatchEvent(event);

  return event;
};

afterEach(() => {
  app.stop();
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

describe('combobox peer — ARIA', () => {
  it('fills missing ARIA 1.2 combobox attributes on connect', async () => {
    await mount(markup());
    expect(q().getAttribute('role')).toBe('combobox');
    expect(q().getAttribute('aria-autocomplete')).toBe('list');
    expect(q().getAttribute('aria-expanded')).toBe('false');
    expect(q().getAttribute('autocomplete')).toBe('off');
    expect(list().getAttribute('role')).toBe('listbox');
    expect(list().id).toMatch(/^nowo-combobox-list-\d+$/);
    expect(q().getAttribute('aria-controls')).toBe(list().id);
    for (const option of options()) {
      expect(option.getAttribute('role')).toBe('option');
      expect(option.getAttribute('aria-selected')).toBe('false');
      expect(option.id).toMatch(new RegExp(`^${list().id}-option-\\d+$`));
    }
  });

  it('keeps host ids and ARIA attributes and marks the preselected option', async () => {
    await mount(
      markup('', 'aria-controls="custom" aria-autocomplete="both" role="searchbox"', '2').replace(
        'data-combobox-target="list"',
        'id="my-list" role="tree" data-combobox-target="list"',
      ),
    );
    expect(list().id).toBe('my-list');
    expect(list().getAttribute('role')).toBe('tree');
    expect(q().getAttribute('aria-controls')).toBe('custom');
    expect(q().getAttribute('aria-autocomplete')).toBe('both');
    expect(q().getAttribute('role')).toBe('searchbox');
    expect(options()[1].getAttribute('aria-selected')).toBe('true');
    expect(options()[0].getAttribute('aria-selected')).toBe('false');
  });
});

describe('combobox peer — filtering and selection', () => {
  it('filters options, toggles the empty target and clears a stale value', async () => {
    const controller = await mount(markup());
    v().value = '1';
    q().value = 'beta';
    controller.filter();
    expect(options()[0].hidden).toBe(true);
    expect(options()[1].hidden).toBe(false);
    expect(empty().hidden).toBe(true);
    expect(v().value).toBe('');

    q().value = 'zzz';
    controller.filter();
    expect(empty().hidden).toBe(false);

    v().value = '1';
    q().value = 'ALPHA';
    controller.filter();
    expect(v().value).toBe('1');

    v().value = '99';
    q().value = 'alpha';
    controller.filter();
    expect(v().value).toBe('');
  });

  it('matches on text content when data-search is missing and works without an empty target', async () => {
    const controller = await mount(markup('', '', '', false));
    q().value = 'gamma';
    controller.filter();
    expect(options()[2].hidden).toBe(false);
    expect(options()[0].hidden).toBe(true);
  });

  it('selects on click, writes value / label, emits events and closes', async () => {
    const controller = await mount(markup());
    const changed = vi.fn();
    const comboboxChange = vi.fn();
    v().addEventListener('change', changed);
    document.querySelector('form')!.addEventListener('combobox:change', comboboxChange);
    controller.open();
    expect(list().hidden).toBe(false);
    expect(q().getAttribute('aria-expanded')).toBe('true');

    options()[1].click();
    expect(v().value).toBe('2');
    expect(q().value).toBe('Beta');
    expect(options()[1].classList.contains('is-selected')).toBe(true);
    expect(options()[1].getAttribute('aria-selected')).toBe('true');
    expect(options()[0].getAttribute('aria-selected')).toBe('false');
    expect(changed).toHaveBeenCalledTimes(1);
    expect((comboboxChange.mock.calls[0][0] as CustomEvent).detail).toEqual({ value: '2', label: 'Beta' });
    expect(list().hidden).toBe(true);
    expect(document.activeElement).toBe(q());

    options()[2].click();
    expect(q().value).toBe('Gamma');
  });

  it('ignores options without a value and non-element targets', async () => {
    const controller = await mount(markup());
    options()[3].click();
    expect(v().value).toBe('');
    const event = new Event('click', { cancelable: true });
    controller.select(event);
    expect(event.defaultPrevented).toBe(true);
    expect(v().value).toBe('');
  });

  it('honours custom / empty selected and active classes', async () => {
    const controller = await mount(
      markup('data-combobox-selected-class-value="" data-combobox-active-class-value=""'),
    );
    controller.onQueryFocus();
    key('ArrowDown');
    expect(options()[0].className).toBe('');
    options()[0].click();
    expect(options()[0].className).toBe('');
    expect(options()[0].getAttribute('aria-selected')).toBe('true');
  });

  it('opens on focus, toggles and closes on outside pointerdown', async () => {
    const controller = await mount(markup());
    q().dispatchEvent(new Event('focus'));
    expect(list().hidden).toBe(false);

    list().dispatchEvent(new Event('pointerdown', { bubbles: true }));
    expect(list().hidden).toBe(false);

    document.getElementById('outside')!.dispatchEvent(new Event('pointerdown', { bubbles: true }));
    expect(list().hidden).toBe(true);

    document.getElementById('outside')!.dispatchEvent(new Event('pointerdown', { bubbles: true }));
    expect(list().hidden).toBe(true);

    controller.toggle();
    expect(list().hidden).toBe(false);
    controller.toggle();
    expect(list().hidden).toBe(true);
  });
});

describe('combobox peer — keyboard', () => {
  it('ArrowDown / ArrowUp open the list and move the active option with wrap-around', async () => {
    await mount(markup());
    const scroll = vi.fn();
    for (const option of options()) {
      option.scrollIntoView = scroll;
    }

    expect(key('ArrowDown').defaultPrevented).toBe(true);
    expect(list().hidden).toBe(false);
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[0].id);
    expect(options()[0].classList.contains('is-active')).toBe(true);
    expect(scroll).toHaveBeenCalledWith({ block: 'nearest' });

    key('ArrowDown');
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[1].id);
    expect(options()[0].classList.contains('is-active')).toBe(false);

    key('ArrowUp');
    key('ArrowUp');
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[3].id);
    key('ArrowDown');
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[0].id);
  });

  it('ArrowUp on a closed list activates the last option; Alt+ArrowDown only opens', async () => {
    const controller = await mount(markup());
    key('ArrowUp');
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[3].id);
    controller.close();
    expect(q().hasAttribute('aria-activedescendant')).toBe(false);

    key('ArrowDown', { altKey: true });
    expect(list().hidden).toBe(false);
    expect(q().hasAttribute('aria-activedescendant')).toBe(false);
  });

  it('arrows with no visible option clear the active descendant; filtering drops a hidden active option', async () => {
    const controller = await mount(markup());
    key('ArrowDown');
    q().value = 'beta';
    controller.filter();
    expect(q().hasAttribute('aria-activedescendant')).toBe(false);

    key('ArrowDown');
    expect(q().getAttribute('aria-activedescendant')).toBe(options()[1].id);
    q().value = 'zzz';
    controller.filter();
    key('ArrowDown');
    expect(q().hasAttribute('aria-activedescendant')).toBe(false);
  });

  it('Enter selects the active option, else the first visible one when no value is set', async () => {
    await mount(markup());
    key('ArrowDown');
    key('ArrowDown');
    expect(key('Enter').defaultPrevented).toBe(true);
    expect(v().value).toBe('2');

    // closed list: Enter submits normally
    expect(key('Enter').defaultPrevented).toBe(false);

    // open, value already set, nothing active: Enter is left to the form
    q().dispatchEvent(new Event('focus'));
    expect(key('Enter').defaultPrevented).toBe(false);

    v().value = '';
    q().value = 'alp';
    q().dispatchEvent(new Event('input'));
    expect(key('Enter').defaultPrevented).toBe(true);
    expect(v().value).toBe('1');
    expect(q().value).toBe('Alpha');
  });

  it('Enter with no visible option does nothing', async () => {
    const controller = await mount(markup());
    controller.open();
    q().value = 'zzz';
    controller.filter();
    expect(key('Enter').defaultPrevented).toBe(false);
  });

  it('Escape closes (prevented only when open), Tab closes, other keys are ignored', async () => {
    const controller = await mount(markup());
    controller.open();
    expect(key('Escape').defaultPrevented).toBe(true);
    expect(list().hidden).toBe(true);
    expect(key('Escape').defaultPrevented).toBe(false);

    controller.open();
    expect(key('Tab').defaultPrevented).toBe(false);
    expect(list().hidden).toBe(true);

    controller.open();
    expect(key('a').defaultPrevented).toBe(false);
    expect(list().hidden).toBe(false);
  });
});

describe('combobox peer — required value', () => {
  it('lets the submit through when a value is set', async () => {
    await mount(markup('', '', '1'));
    const event = new Event('submit', { cancelable: true });
    document.querySelector('form')!.dispatchEvent(event);
    expect(event.defaultPrevented).toBe(false);
  });

  it('blocks submit with the neutral default message', async () => {
    await mount(markup());
    const report = vi.spyOn(q(), 'reportValidity').mockReturnValue(false);
    const event = new Event('submit', { cancelable: true });
    document.querySelector('form')!.dispatchEvent(event);
    expect(event.defaultPrevented).toBe(true);
    expect(q().validationMessage).toBe(DEFAULT_REQUIRED_MESSAGE);
    expect(DEFAULT_REQUIRED_MESSAGE).toBe('Please select an option.');
    expect(report).toHaveBeenCalled();
    expect(list().hidden).toBe(false);
    expect(document.activeElement).toBe(q());

    q().dispatchEvent(new Event('input'));
    expect(q().validationMessage).toBe('');
  });

  it('uses the legacy data-required-message attribute, then the requiredMessage value', async () => {
    await mount(markup('', 'data-required-message="Pick one"'));
    vi.spyOn(q(), 'reportValidity').mockReturnValue(false);
    document.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
    expect(q().validationMessage).toBe('Pick one');
    app.stop();

    await mount(markup('data-combobox-required-message-value="Choose an issue"', 'data-required-message="Pick one"'));
    vi.spyOn(q(), 'reportValidity').mockReturnValue(false);
    document.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
    expect(q().validationMessage).toBe('Choose an issue');
  });
});

describe('combobox peer — lifecycle', () => {
  it('removes the document listener on disconnect', async () => {
    await mount(markup());
    const remove = vi.spyOn(document, 'removeEventListener');
    document.querySelector('form')!.remove();
    await tick();
    expect(remove).toHaveBeenCalledWith('pointerdown', expect.any(Function));
  });
});
