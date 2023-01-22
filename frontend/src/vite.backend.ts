import { BackendModule, InitOptions, ModuleType, ReadCallback, ResourceKey, Services, TOptions } from 'i18next'

export type ViteBackendOptions = {}

export class ViteBackend implements BackendModule {
  private services: Services;
  private options: TOptions;
  private allOptions: InitOptions;
  private modules: Record<string, () => Promise<unknown>>;
  public type: 'backend' = 'backend';
  static type: 'backend' = 'backend';

  constructor (services: Services, options: ViteBackendOptions, allOptions = {}) {
    this.services = services;
    this.options = options;
    this.allOptions = allOptions;
    this.modules = {};
    this.init(services, options, allOptions)
  }

  init (services: Services, options: ViteBackendOptions, allOptions = {}) {
    this.services = services
    this.options = options;
    this.allOptions = allOptions;
    this.modules = import.meta.glob('/src/pages/**/locales/*.json');
  }

  async read (language: string, namespace: string, callback: ReadCallback) {
    for (const key in this.modules) {
        const [,,,moduleNamespace,,localeFile] = key.split('/');
        const [moduleLanguage] = localeFile.split('.');

        if (language !== moduleLanguage || namespace !== moduleNamespace) continue;

        this.modules[key]().then((value) => {
            callback(null, value as ResourceKey)
        }, (error) => {
            callback(error, null)
        });
        break;
    }
    callback(null, {})
  }
}
