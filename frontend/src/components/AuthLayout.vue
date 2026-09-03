<script setup>
import AppLogo from './AppLogo.vue'
import LanguageSwitcher from './LanguageSwitcher.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n({ useScope: 'global' })
</script>

<template>
  <main class="auth-page">
    <section class="auth-story">
      <div class="auth-story__top"><AppLogo light /><LanguageSwitcher light /></div>
      <div class="auth-story__copy">
        <span class="auth-story__eyebrow">{{ t('auth.story.eyebrow') }}</span>
        <h1>{{ t('auth.story.title') }}</h1>
        <p>{{ t('auth.story.description') }}</p>
      </div>
      <div class="mini-survey" aria-hidden="true">
        <div class="mini-survey__top"><span></span><small>{{ t('auth.story.active') }}</small></div>
        <strong>{{ t('auth.story.sampleQuestion') }}</strong>
        <div class="mini-survey__line"></div>
        <div class="mini-survey__choices"><i></i><i></i><i></i><i></i><i></i></div>
        <div class="mini-survey__footer"><span>{{ t('auth.story.today') }}</span><b>↗</b></div>
      </div>
      <small class="auth-story__footer">{{ t('auth.story.footer') }}</small>
    </section>

    <section class="auth-content">
      <div class="auth-content__mobile-logo"><AppLogo /><LanguageSwitcher /></div>
      <div class="auth-card"><slot /></div>
    </section>
  </main>
</template>

<style scoped>
.auth-page { min-height: 100vh; display: grid; grid-template-columns: minmax(360px, 44%) 1fr; background: white; }
.auth-story { position: relative; overflow: hidden; min-height: 100vh; padding: clamp(2rem, 5vw, 4rem); display: flex; flex-direction: column; background: var(--navy); color: white; }
.auth-story__top { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; }
.auth-story::before { content: ''; position: absolute; width: 430px; height: 430px; right: -210px; top: -160px; border: 70px solid rgba(156,232,207,.08); border-radius: 50%; }
.auth-story::after { content: ''; position: absolute; width: 270px; height: 270px; left: -175px; bottom: -110px; border: 55px solid rgba(239,118,95,.1); border-radius: 50%; }
.auth-story__copy { position: relative; z-index: 1; margin: auto 0 2.4rem; max-width: 510px; }
.auth-story__eyebrow { display: block; margin-bottom: 1rem; color: var(--mint); font-size: .73rem; font-weight: 850; letter-spacing: .15em; text-transform: uppercase; }
.auth-story h1 { max-width: 500px; margin-bottom: 1.1rem; font-size: clamp(2.5rem, 5vw, 4.7rem); line-height: .98; letter-spacing: -.065em; }
.auth-story p { max-width: 480px; margin: 0; color: #bdd0d5; font-size: 1rem; line-height: 1.7; }
.mini-survey { position: relative; z-index: 1; max-width: 390px; padding: 1.15rem; border: 1px solid rgba(255,255,255,.12); border-radius: 15px; background: rgba(255,255,255,.075); backdrop-filter: blur(8px); transform: rotate(-1deg); }
.mini-survey__top { display: flex; justify-content: space-between; align-items: center; margin-bottom: .8rem; }
.mini-survey__top span { width: 36px; height: 6px; border-radius: 999px; background: var(--mint); }
.mini-survey__top small { color: #aac0c5; }
.mini-survey strong { font-size: .94rem; }
.mini-survey__line { width: 80%; height: 6px; margin: .7rem 0 .8rem; border-radius: 999px; background: rgba(255,255,255,.12); }
.mini-survey__choices { display: flex; gap: .5rem; }
.mini-survey__choices i { width: 25px; height: 25px; border: 1px solid rgba(255,255,255,.18); border-radius: 7px; }
.mini-survey__choices i:nth-child(4) { border-color: var(--mint); background: var(--mint); }
.mini-survey__footer { display: flex; justify-content: space-between; margin-top: .85rem; color: #a9bdc2; font-size: .7rem; }
.mini-survey__footer b { color: var(--mint); }
.auth-story__footer { position: relative; z-index: 1; margin-top: 2rem; color: #849da4; }
.auth-content { min-height: 100vh; display: grid; place-items: center; padding: 2rem; background: #fcfdfc; }
.auth-card { width: min(410px, 100%); }
.auth-content__mobile-logo { display: none; }
@media (max-width: 820px) {
  .auth-page { display: block; }
  .auth-story { display: none; }
  .auth-content { display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2.5rem; padding: 1.5rem; }
  .auth-card, .auth-content__mobile-logo { width: 100%; max-width: 410px; min-width: 0; }
  .auth-content__mobile-logo { display: flex; align-items: center; justify-content: space-between; }
}
</style>
