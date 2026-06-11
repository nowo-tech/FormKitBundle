import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createBundleLogger, getLogger, setBundleLogger } from './logger';

describe('logger', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  it('scriptLoaded logs with and without build time', () => {
    const noBuild = createBundleLogger('form-kit-help-modal');
    noBuild.scriptLoaded();
    expect(console.log).toHaveBeenCalled();

    const withBuild = createBundleLogger('form-kit-help-modal', {
      buildTime: '2026-03-30T10:00:00Z',
    });
    withBuild.scriptLoaded();
    expect(console.log).toHaveBeenCalledTimes(2);
  });

  it('alwaysLog false keeps level logs silent', () => {
    const log = createBundleLogger('x', { alwaysLog: false });
    log.setDebug(true);
    log.debug('a');
    log.info('b');
    log.warn('c');
    log.error('d');
    expect(console.debug).not.toHaveBeenCalled();
    expect(console.info).not.toHaveBeenCalled();
    expect(console.warn).not.toHaveBeenCalled();
    expect(console.error).not.toHaveBeenCalled();
  });

  it('alwaysLog true emits all levels and stringifies objects', () => {
    const log = createBundleLogger('x', { alwaysLog: true });
    log.debug();
    log.info('info');
    log.warn('warn', { a: 1 });
    log.error('error', new Error('boom'));

    expect(console.debug).toHaveBeenCalled();
    expect(console.info).toHaveBeenCalled();
    expect(console.warn).toHaveBeenCalled();
    expect(console.error).toHaveBeenCalled();
  });

  it('getLogger returns no-op by default and setBundleLogger overrides it', () => {
    const noOp = getLogger();
    noOp.scriptLoaded();
    noOp.debug('x');
    noOp.info('x');
    noOp.warn('x');
    noOp.error('x');
    expect(console.debug).not.toHaveBeenCalled();

    const real = createBundleLogger('form-kit-help-modal', { alwaysLog: true });
    setBundleLogger(real);
    const registered = getLogger();
    registered.debug('ok');
    expect(console.debug).toHaveBeenCalled();
  });
});

