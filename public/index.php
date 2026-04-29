<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ACBRView — NFS-e Municípios</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
          colors: {
            brand: { 50:'#eff6ff', 100:'#dbeafe', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 900:'#1e3a8a' }
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
  <style>
    [x-cloak] { display: none !important; }
    .link-chip { word-break: break-all; }
    .card-enter { animation: fadeSlideIn .15s ease-out both; }
    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(6px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    /* Scrollbar fina */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  </style>
</head>
<body class="bg-slate-50 font-sans antialiased min-h-screen" x-data="acbrApp()" x-init="init()">

  <!-- ═══════════════════════  HEADER  ═══════════════════════ -->
  <header class="bg-gradient-to-r from-slate-800 to-slate-900 text-white shadow-xl">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <!-- Logo mark -->
        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center shadow-lg flex-shrink-0">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight leading-none">ACBRView</h1>
          <p class="text-slate-400 text-xs mt-0.5">NFS-e — Provedores e Municípios</p>
        </div>
      </div>

      <!-- GitHub link -->
      <a
        href="https://github.com/RechembakJS/ACBRView"
        target="_blank"
        rel="noopener noreferrer"
        class="hidden sm:flex items-center gap-2 text-sm text-slate-300 hover:text-white bg-slate-700/60 hover:bg-slate-700 border border-slate-600 hover:border-slate-400 rounded-lg px-3 py-1.5 transition"
        title="Ver código no GitHub"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.021c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.009-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844a9.59 9.59 0 012.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0022 12.021C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
        </svg>
        RechembakJS/ACBRView
      </a>

      <!-- Stats / status -->
      <div class="flex flex-wrap items-center gap-2 text-xs" x-cloak>
        <template x-if="meta.total_municipios">
          <span class="bg-slate-700 text-slate-200 rounded-full px-3 py-1 font-medium">
            <span x-text="meta.total_municipios.toLocaleString('pt-BR')"></span> municípios
          </span>
        </template>
        <template x-if="meta.atualizado_em">
          <span class="bg-slate-700 text-slate-300 rounded-full px-3 py-1 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Atualizado <span x-text="formatDate(meta.atualizado_em)"></span>
          </span>
        </template>
        <template x-if="meta.ultimo_erro && meta.ultimo_erro.mensagem">
          <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-full px-3 py-1 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            Usando cache anterior
          </span>
        </template>
      </div>
    </div>
  </header>

  <!-- ═══════════════════════  SEARCH BAR  ═══════════════════════ -->
  <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-200 shadow-sm">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 py-3">
      <div class="flex flex-col sm:flex-row gap-2">

        <!-- Campo de busca -->
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
          </svg>
          <input
            id="busca"
            type="search"
            placeholder="Buscar por nome do município ou provedor…"
            class="w-full pl-9 pr-9 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-slate-400 transition"
            x-model="busca"
            @input.debounce.250ms="aplicarFiltro()"
            autocomplete="off"
            aria-label="Buscar municípios"
          />
          <button
            x-show="busca !== ''"
            @click="busca = ''; aplicarFiltro()"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
            aria-label="Limpar busca"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Filtro UF -->
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <select
            x-model="ufFiltro"
            @change="aplicarFiltro()"
            class="pl-9 pr-8 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer transition min-w-[130px]"
            aria-label="Filtrar por UF"
          >
            <option value="">Todos os estados</option>
            <template x-for="uf in ufsDisponiveis" :key="uf">
              <option :value="uf" x-text="uf"></option>
            </template>
          </select>
          <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>

        <!-- Filtro provedor -->
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
          </svg>
          <select
            x-model="provedorFiltro"
            @change="aplicarFiltro()"
            class="pl-9 pr-8 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer transition min-w-[160px]"
            aria-label="Filtrar por provedor"
          >
            <option value="">Todos os provedores</option>
            <template x-for="p in provedoresDisponiveis" :key="p">
              <option :value="p" x-text="p"></option>
            </template>
          </select>
          <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>

      <!-- Contagem de resultados -->
      <div class="mt-2 flex items-center justify-between text-xs text-slate-500" x-cloak>
        <span>
          <template x-if="!carregando && municipios.length > 0">
            <span>
              Exibindo <strong class="text-slate-700" x-text="paginaAtual.length"></strong>
              de <strong class="text-slate-700" x-text="filtrados.length.toLocaleString('pt-BR')"></strong>
              <template x-if="filtrados.length !== municipios.length">
                <span> (de <span x-text="municipios.length.toLocaleString('pt-BR')"></span> no total)</span>
              </template>
            </span>
          </template>
        </span>
        <template x-if="totalPaginas > 1">
          <span x-text="`Página ${pagina} de ${totalPaginas}`"></span>
        </template>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════  CONTEÚDO PRINCIPAL  ═══════════════════════ -->
  <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 py-6">

    <!-- Estado: carregando -->
    <div x-show="carregando" class="flex flex-col items-center justify-center py-32 text-slate-400">
      <svg class="w-10 h-10 animate-spin mb-4 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <p class="text-sm font-medium text-slate-500">Carregando dados…</p>
      <p class="text-xs mt-1 text-slate-400">O primeiro acesso pode demorar alguns segundos enquanto o cache é construído.</p>
    </div>

    <!-- Estado: erro -->
    <div x-show="!carregando && erro" class="flex flex-col items-center justify-center py-32" x-cloak>
      <div class="bg-red-50 border border-red-200 rounded-2xl p-8 max-w-md text-center">
        <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <p class="font-semibold text-red-700 mb-1">Não foi possível carregar os dados</p>
        <p class="text-sm text-red-500" x-text="erro"></p>
        <button @click="init()" class="mt-4 text-sm bg-red-100 hover:bg-red-200 text-red-700 font-medium px-4 py-2 rounded-lg transition">
          Tentar novamente
        </button>
      </div>
    </div>

    <!-- Estado: sem resultados na busca -->
    <div x-show="!carregando && !erro && municipios.length > 0 && filtrados.length === 0" class="flex flex-col items-center justify-center py-32" x-cloak>
      <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
      </svg>
      <p class="font-semibold text-slate-500">Nenhum município encontrado</p>
      <p class="text-sm text-slate-400 mt-1">Tente outros termos ou limpe os filtros.</p>
      <button @click="busca=''; ufFiltro=''; provedorFiltro=''; aplicarFiltro()" class="mt-4 text-sm text-blue-600 hover:text-blue-800 font-medium underline underline-offset-2 transition">
        Limpar filtros
      </button>
    </div>

    <!-- Grade de cards -->
    <div
      x-show="!carregando && !erro && paginaAtual.length > 0"
      x-cloak
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
    >
      <template x-for="m in paginaAtual" :key="m.codigo_ibge">
        <article
          class="card-enter bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col overflow-hidden"
          :class="{ 'ring-2 ring-blue-400': expandidos[m.codigo_ibge] }"
        >
          <!-- Cabeçalho do card -->
          <div class="p-4 flex-1">
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <h2 class="font-semibold text-slate-800 text-sm leading-snug truncate" x-text="m.nome" :title="m.nome"></h2>
                <p class="text-xs text-slate-400 mt-0.5" x-text="'IBGE: ' + m.codigo_ibge"></p>
              </div>
              <!-- Badge UF -->
              <span
                class="flex-shrink-0 text-xs font-bold px-2 py-0.5 rounded-md"
                :class="badgeUF(m.uf)"
                x-text="m.uf"
                :title="nomeRegiao(m.uf)"
              ></span>
            </div>

            <!-- Provedor + Versão -->
            <div class="flex flex-wrap items-center gap-1.5 mt-3">
              <span
                class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                :class="badgeProvedor(m.provedor)"
                x-text="m.provedor || 'Sem provedor'"
              ></span>
              <template x-if="m.versao">
                <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full" x-text="'v' + m.versao"></span>
              </template>
            </div>

            <!-- Resumo de links -->
            <div class="mt-3 text-xs text-slate-500 flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
              </svg>
              <span x-text="m.links.length + (m.links.length === 1 ? ' endpoint' : ' endpoints')"></span>
            </div>
          </div>

          <!-- Botão de expandir links -->
          <template x-if="m.links.length > 0">
            <button
              @click="toggleExpand(m.codigo_ibge)"
              class="w-full px-4 py-2.5 flex items-center justify-between text-xs font-medium border-t border-slate-100 hover:bg-slate-50 transition text-slate-600 hover:text-blue-600"
              :aria-expanded="!!expandidos[m.codigo_ibge]"
              :aria-label="'Ver endpoints de ' + m.nome"
            >
              <span x-text="expandidos[m.codigo_ibge] ? 'Ocultar endpoints' : 'Ver endpoints'"></span>
              <svg
                class="w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-180': expandidos[m.codigo_ibge] }"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
          </template>

          <!-- Lista de links (expandível) -->
          <div
            x-show="expandidos[m.codigo_ibge]"
            x-collapse
            class="border-t border-slate-100 bg-slate-50 divide-y divide-slate-100"
          >
            <template x-for="link in m.links" :key="link.chave">
              <div class="px-4 py-2.5">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5" x-text="link.chave"></p>
                <a
                  :href="link.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="link-chip text-xs text-blue-600 hover:text-blue-800 hover:underline transition flex items-start gap-1 group"
                  :title="link.url"
                >
                  <svg class="w-3 h-3 flex-shrink-0 mt-0.5 text-blue-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                  </svg>
                  <span x-text="link.url"></span>
                </a>
              </div>
            </template>
          </div>
        </article>
      </template>
    </div>

    <!-- Paginação -->
    <nav
      x-show="!carregando && !erro && totalPaginas > 1"
      x-cloak
      class="mt-8 flex items-center justify-center gap-1 flex-wrap"
      aria-label="Paginação"
    >
      <button
        @click="irParaPagina(pagina - 1)"
        :disabled="pagina === 1"
        class="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
        aria-label="Página anterior"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>

      <template x-for="p in paginasVisiveis" :key="p">
        <button
          @click="p !== '…' && irParaPagina(p)"
          :class="{
            'bg-blue-600 text-white border-blue-600 font-semibold': p === pagina,
            'bg-white text-slate-600 border-slate-200 hover:bg-slate-50': p !== pagina && p !== '…',
            'cursor-default text-slate-400 border-transparent bg-transparent': p === '…'
          }"
          class="min-w-[36px] h-9 px-2 text-sm rounded-lg border transition"
          :aria-current="p === pagina ? 'page' : false"
          x-text="p"
        ></button>
      </template>

      <button
        @click="irParaPagina(pagina + 1)"
        :disabled="pagina === totalPaginas"
        class="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
        aria-label="Próxima página"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </nav>
  </main>

  <!-- ═══════════════════════  FOOTER  ═══════════════════════ -->
  <footer class="mt-12 border-t border-slate-200 bg-white">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
      <span>ACBRView — dados do <a href="https://sourceforge.net/p/acbr/code/" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline">ACBr SVN</a></span>
      <span x-cloak x-show="meta.atualizado_em">
        Cache atualizado em <span x-text="formatDate(meta.atualizado_em)"></span>
        &nbsp;·&nbsp;
        <span x-text="consultasHoje()"></span> consulta(s) remotas hoje
      </span>
      <a
        href="https://github.com/RechembakJS/ACBRView"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center gap-1.5 text-slate-400 hover:text-slate-700 transition"
        title="Ver código no GitHub"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.021c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.009-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844a9.59 9.59 0 012.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0022 12.021C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
        </svg>
        GitHub
      </a>
    </div>
  </footer>

  <script>
    function acbrApp() {
      return {
        municipios:        [],
        filtrados:         [],
        meta:              {},
        busca:             '',
        ufFiltro:          '',
        provedorFiltro:    '',
        pagina:            1,
        porPagina:         48,
        expandidos:        {},
        carregando:        true,
        erro:              null,

        // ── Computed ─────────────────────────────────────────────
        get ufsDisponiveis() {
          return [...new Set(this.municipios.map(m => m.uf))].sort();
        },
        get provedoresDisponiveis() {
          return [...new Set(this.municipios.map(m => m.provedor).filter(Boolean))].sort();
        },
        get totalPaginas() {
          return Math.max(1, Math.ceil(this.filtrados.length / this.porPagina));
        },
        get paginaAtual() {
          const start = (this.pagina - 1) * this.porPagina;
          return this.filtrados.slice(start, start + this.porPagina);
        },
        get paginasVisiveis() {
          return this._gerarPaginas(this.pagina, this.totalPaginas);
        },

        // ── Init ─────────────────────────────────────────────────
        async init() {
          this.carregando = true;
          this.erro = null;
          try {
            const res = await fetch('api.php');
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            this.municipios = data.municipios || [];
            this.meta       = data.meta       || {};
            this.aplicarFiltro();
          } catch (e) {
            this.erro = e.message || 'Não foi possível carregar os dados.';
          } finally {
            this.carregando = false;
          }
        },

        // ── Filtro ───────────────────────────────────────────────
        aplicarFiltro() {
          const q  = this._normalizar(this.busca);
          const uf = this.ufFiltro;
          const pr = this.provedorFiltro;

          this.filtrados = this.municipios.filter(m => {
            if (uf && m.uf !== uf)               return false;
            if (pr && m.provedor !== pr)          return false;
            if (!q)                               return true;

            const haystack = this._normalizar([
              m.nome,
              m.uf,
              m.provedor,
              m.codigo_ibge,
              ...m.links.map(l => l.url),
              ...m.links.map(l => l.chave),
            ].join(' '));

            return haystack.includes(q);
          });

          this.pagina = 1;
        },

        _normalizar(str) {
          return String(str)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
        },

        // ── Paginação ────────────────────────────────────────────
        irParaPagina(n) {
          if (n < 1 || n > this.totalPaginas) return;
          this.pagina = n;
          window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        _gerarPaginas(atual, total) {
          if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

          const pages = new Set([1, total, atual]);
          if (atual > 1) pages.add(atual - 1);
          if (atual < total) pages.add(atual + 1);

          const sorted = [...pages].sort((a, b) => a - b);
          const result = [];

          for (let i = 0; i < sorted.length; i++) {
            if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…');
            result.push(sorted[i]);
          }

          return result;
        },

        // ── Toggle ───────────────────────────────────────────────
        toggleExpand(ibge) {
          this.expandidos = { ...this.expandidos, [ibge]: !this.expandidos[ibge] };
        },

        // ── Visual helpers ───────────────────────────────────────
        badgeUF(uf) {
          const regioes = {
            norte:      ['AM','RR','AP','PA','TO','RO','AC'],
            nordeste:   ['MA','PI','CE','RN','PB','PE','AL','SE','BA'],
            centroeste: ['MT','MS','GO','DF'],
            sudeste:    ['SP','RJ','MG','ES'],
            sul:        ['PR','SC','RS'],
          };
          const classes = {
            norte:      'bg-emerald-100 text-emerald-800',
            nordeste:   'bg-orange-100  text-orange-800',
            centroeste: 'bg-yellow-100  text-yellow-800',
            sudeste:    'bg-blue-100    text-blue-800',
            sul:        'bg-purple-100  text-purple-800',
          };
          for (const [r, ufs] of Object.entries(regioes)) {
            if (ufs.includes(uf)) return classes[r];
          }
          return 'bg-slate-100 text-slate-600';
        },

        nomeRegiao(uf) {
          const mapa = {
            norte:      ['AM','RR','AP','PA','TO','RO','AC'],
            nordeste:   ['MA','PI','CE','RN','PB','PE','AL','SE','BA'],
            centroeste: ['MT','MS','GO','DF'],
            sudeste:    ['SP','RJ','MG','ES'],
            sul:        ['PR','SC','RS'],
          };
          for (const [r, ufs] of Object.entries(mapa)) {
            if (ufs.includes(uf)) return r.charAt(0).toUpperCase() + r.slice(1);
          }
          return uf;
        },

        badgeProvedor(provedor) {
          if (!provedor) return 'bg-slate-100 text-slate-500';
          // Paleta por hash do nome do provedor
          const palettes = [
            'bg-sky-100    text-sky-800',
            'bg-indigo-100 text-indigo-800',
            'bg-violet-100 text-violet-800',
            'bg-pink-100   text-pink-800',
            'bg-rose-100   text-rose-800',
            'bg-teal-100   text-teal-800',
            'bg-cyan-100   text-cyan-800',
            'bg-lime-100   text-lime-800',
            'bg-amber-100  text-amber-800',
            'bg-fuchsia-100 text-fuchsia-800',
          ];
          let hash = 0;
          for (let i = 0; i < provedor.length; i++) hash = (hash * 31 + provedor.charCodeAt(i)) >>> 0;
          return palettes[hash % palettes.length];
        },

        consultasHoje() {
          if (!this.meta.consultas_rede_por_data) return 0;
          const hoje = new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
          return this.meta.consultas_rede_por_data[hoje] ?? 0;
        },

        formatDate(iso) {
          if (!iso) return '';
          try {
            return new Intl.DateTimeFormat('pt-BR', {
              day:    '2-digit', month: '2-digit', year: 'numeric',
              hour:   '2-digit', minute: '2-digit',
              timeZone: 'America/Sao_Paulo',
            }).format(new Date(iso));
          } catch {
            return iso;
          }
        },
      };
    }
  </script>
</body>
</html>
