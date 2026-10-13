import { Controller } from '@hotwired/stimulus';

/** Neutral fallback for {@link ComboboxController.requireValue}; override with the `requiredMessage` value. */
export const DEFAULT_REQUIRED_MESSAGE = 'Please select an option.';

let idCounter = 0;

const ensureId = (el: HTMLElement, prefix: string): string => {
  if (el.id === '') {
    idCounter += 1;
    el.id = `${prefix}-${idCounter}`;
  }

  return el.id;
};

/** `textContent` is never null on an element (only on documents / doctypes). */
const textOf = (el: HTMLElement): string => el.textContent as string;

const setDefaultAttribute = (el: HTMLElement, name: string, value: string): void => {
  if (!el.hasAttribute(name)) {
    el.setAttribute(name, value);
  }
};

/**
 * Opt-in Stimulus peer: editable combobox with list autocomplete (WAI-ARIA 1.2 combobox pattern).
 *
 * The visible `query` input filters a static list of `option` elements; the chosen option's
 * `data-value` is written into the (usually hidden) `value` input that the form submits.
 *
 * Markup contract (identifier `combobox`):
 * - targets: `query` (text/search input), `value` (input submitted), `list` (listbox),
 *   `option` (each option; `data-value`, optional `data-label` and `data-search`), optional `empty`.
 * - values: `open` (bool), `requiredMessage` (string), `selectedClass` / `activeClass` (string).
 * - actions: `filter`, `onQueryFocus`, `onQueryKeydown`, `select`, `open`, `close`, `toggle`,
 *   `requireValue` (on the form `submit`).
 * - event: `combobox:change` (detail `{ value, label }`) on the controller element after a selection.
 *
 * Host-provided ARIA attributes are kept; missing ones (`role`, `aria-expanded`, `aria-controls`,
 * `aria-autocomplete`, `aria-selected`, ids) are filled in on connect.
 */
export default class ComboboxController extends Controller<HTMLElement> {
  static targets = ['query', 'value', 'list', 'option', 'empty'];

  static values = {
    open: { type: Boolean, default: false },
    requiredMessage: { type: String, default: '' },
    selectedClass: { type: String, default: 'is-selected' },
    activeClass: { type: String, default: 'is-active' },
  };

  declare readonly queryTarget: HTMLInputElement;
  declare readonly valueTarget: HTMLInputElement;
  declare readonly listTarget: HTMLElement;
  declare readonly optionTargets: HTMLElement[];
  declare readonly hasEmptyTarget: boolean;
  declare readonly emptyTarget: HTMLElement;
  declare openValue: boolean;
  declare requiredMessageValue: string;
  declare selectedClassValue: string;
  declare activeClassValue: string;

  private activeOption: HTMLElement | null = null;

  /** Set while the controller moves focus back to the query after a selection (keeps the list closed). */
  private refocusing = false;

  private readonly onDocumentPointerDown = (event: Event): void => {
    const target = event.target;
    if (this.openValue && target instanceof Node && !this.element.contains(target)) {
      this.close();
    }
  };

  connect(): void {
    this.applyAria();
    this.filter();
    this.syncListVisibility();
    document.addEventListener('pointerdown', this.onDocumentPointerDown);
  }

  disconnect(): void {
    document.removeEventListener('pointerdown', this.onDocumentPointerDown);
  }

  open(): void {
    this.openValue = true;
    this.syncListVisibility();
  }

  close(): void {
    this.openValue = false;
    this.setActive(null);
    this.syncListVisibility();
  }

  toggle(): void {
    if (this.openValue) {
      this.close();
    } else {
      this.open();
    }
  }

  filter(): void {
    this.queryTarget.setCustomValidity('');
    const needle = this.queryTarget.value.trim().toLowerCase();
    let visible = 0;

    for (const option of this.optionTargets) {
      const haystack = (option.dataset.search ?? textOf(option)).toLowerCase();
      const match = needle === '' || haystack.includes(needle);
      option.hidden = !match;
      if (match) {
        visible += 1;
      }
    }

    if (this.hasEmptyTarget) {
      this.emptyTarget.hidden = visible > 0;
    }

    if (this.activeOption !== null && this.activeOption.hidden) {
      this.setActive(null);
    }

    if (needle !== '' && this.valueTarget.value !== '') {
      const selected = this.optionTargets.find((el) => el.dataset.value === this.valueTarget.value);
      const selectedLabel = selected === undefined ? '' : this.labelOf(selected);
      if (selectedLabel.toLowerCase() !== needle) {
        this.valueTarget.value = '';
      }
    }
  }

  select(event: Event): void {
    event.preventDefault();
    const option = event.currentTarget;
    if (option instanceof HTMLElement) {
      this.choose(option);
    }
  }

  onQueryFocus(): void {
    if (this.refocusing) {
      return;
    }
    this.openValue = true;
    this.filter();
    this.syncListVisibility();
  }

  onQueryKeydown(event: KeyboardEvent): void {
    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp': {
        event.preventDefault();
        const wasOpen = this.openValue;
        if (!wasOpen) {
          this.filter();
          this.open();
        }
        if (event.altKey) {
          return;
        }
        this.move(event.key === 'ArrowDown' ? 1 : -1, wasOpen);
        return;
      }
      case 'Escape':
        if (this.openValue) {
          event.preventDefault();
        }
        this.close();
        return;
      case 'Tab':
        this.close();
        return;
      case 'Enter': {
        if (!this.openValue) {
          return;
        }
        const target = this.activeOption ?? (this.valueTarget.value === '' ? this.visibleOptions()[0] : undefined);
        if (target !== undefined) {
          event.preventDefault();
          this.choose(target);
        }
        return;
      }
      default:
    }
  }

  requireValue(event: Event): void {
    if (this.valueTarget.value.trim() !== '') {
      return;
    }
    event.preventDefault();
    this.openValue = true;
    this.filter();
    this.syncListVisibility();
    // Focus first: the focus action re-runs filter(), which clears the custom validity message.
    this.queryTarget.focus();
    this.queryTarget.setCustomValidity(this.requiredMessage());
    this.queryTarget.reportValidity();
  }

  private requiredMessage(): string {
    if (this.requiredMessageValue !== '') {
      return this.requiredMessageValue;
    }

    return this.queryTarget.getAttribute('data-required-message') || DEFAULT_REQUIRED_MESSAGE;
  }

  private choose(option: HTMLElement): void {
    const value = option.dataset.value ?? '';
    if (value === '') {
      return;
    }
    const label = this.labelOf(option);
    this.valueTarget.value = value;
    this.queryTarget.value = label;
    this.queryTarget.setCustomValidity('');
    for (const candidate of this.optionTargets) {
      const selected = candidate === option;
      if (this.selectedClassValue !== '') {
        candidate.classList.toggle(this.selectedClassValue, selected);
      }
      candidate.setAttribute('aria-selected', selected ? 'true' : 'false');
    }
    this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));
    this.dispatch('change', { detail: { value, label } });
    this.close();
    this.refocusing = true;
    this.queryTarget.focus();
    this.refocusing = false;
  }

  private labelOf(option: HTMLElement): string {
    return option.dataset.label ?? textOf(option).trim();
  }

  private visibleOptions(): HTMLElement[] {
    return this.optionTargets.filter((el) => !el.hidden);
  }

  private move(step: 1 | -1, wasOpen: boolean): void {
    const visible = this.visibleOptions();
    if (visible.length === 0) {
      this.setActive(null);
      return;
    }
    const current = this.activeOption === null ? -1 : visible.indexOf(this.activeOption);
    let next: number;
    if (current === -1 || !wasOpen) {
      next = step === 1 ? 0 : visible.length - 1;
    } else {
      next = (current + step + visible.length) % visible.length;
    }
    this.setActive(visible[next]);
  }

  private setActive(option: HTMLElement | null): void {
    if (this.activeOption !== null && this.activeClassValue !== '') {
      this.activeOption.classList.remove(this.activeClassValue);
    }
    this.activeOption = option;
    if (option === null) {
      this.queryTarget.removeAttribute('aria-activedescendant');
      return;
    }
    if (this.activeClassValue !== '') {
      option.classList.add(this.activeClassValue);
    }
    this.queryTarget.setAttribute('aria-activedescendant', option.id);
    if (typeof option.scrollIntoView === 'function') {
      option.scrollIntoView({ block: 'nearest' });
    }
  }

  private applyAria(): void {
    const listId = ensureId(this.listTarget, 'nowo-combobox-list');
    setDefaultAttribute(this.listTarget, 'role', 'listbox');
    setDefaultAttribute(this.queryTarget, 'role', 'combobox');
    setDefaultAttribute(this.queryTarget, 'aria-autocomplete', 'list');
    setDefaultAttribute(this.queryTarget, 'aria-controls', listId);
    setDefaultAttribute(this.queryTarget, 'autocomplete', 'off');
    for (const option of this.optionTargets) {
      ensureId(option, `${listId}-option`);
      setDefaultAttribute(option, 'role', 'option');
      const selected = this.valueTarget.value !== '' && option.dataset.value === this.valueTarget.value;
      option.setAttribute('aria-selected', selected ? 'true' : 'false');
    }
  }

  private syncListVisibility(): void {
    this.listTarget.hidden = !this.openValue;
    this.queryTarget.setAttribute('aria-expanded', this.openValue ? 'true' : 'false');
  }
}
