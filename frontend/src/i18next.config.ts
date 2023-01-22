import i18n, { Resource } from "i18next";
import { initReactI18next } from "react-i18next";
import LanguageDetector from 'i18next-browser-languagedetector';
import { ViteBackend, ViteBackendOptions } from "./vite.backend";

i18n
  .use(LanguageDetector)
  .use(ViteBackend)
  .use(initReactI18next)
  .init({    
    fallbackLng: 'en',
    supportedLngs: ['en', 'es'],
    debug: true,
    interpolation: {
      escapeValue: false, // not needed for react as it escapes by default
    },
  });

export default i18n;