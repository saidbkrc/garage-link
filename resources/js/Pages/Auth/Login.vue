<template>
    <div class="flex min-h-screen w-full flex-col md:flex-row overflow-hidden bg-background-light dark:bg-background-dark font-display">
        
        <!-- Left Side: Login Form -->
        <div class="flex w-full flex-col justify-center bg-white dark:bg-background-dark md:w-1/2 p-8 lg:p-24 xl:p-32">
            <div class="mx-auto w-full max-w-[440px]">
                
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-10">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">PigaSoft</span>
                </div>

                <!-- Header & Subtext -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white leading-tight">Hoş Geldiniz</h1>
                    <p class="mt-2 text-slate-500 dark:text-slate-400">Bayi panelinize giriş yapın</p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <span class="material-symbols-outlined text-lg">error</span>
                        <span class="text-sm font-medium">{{ error }}</span>
                    </div>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="handleLogin" class="space-y-5">
                    
                    <!-- Email Field -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">E-posta</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-4 text-slate-400">mail</span>
                            <input 
                                v-model="form.email"
                                type="email" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-12 py-3.5 text-slate-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': errors.email }"
                                placeholder="E-posta adresinizi girin"
                                required
                            />
                        </div>
                        <span v-if="errors.email" class="text-xs text-red-500">{{ errors.email }}</span>
                    </div>

                    <!-- Password Field -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Şifre</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-4 text-slate-400">lock</span>
                            <input 
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-12 py-3.5 text-slate-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': errors.password }"
                                placeholder="Şifrenizi girin"
                                required
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                            >
                                <span class="material-symbols-outlined">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </div>
                        <span v-if="errors.password" class="text-xs text-red-500">{{ errors.password }}</span>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex items-center justify-between py-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                v-model="form.remember"
                                type="checkbox" 
                                class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm text-slate-600 dark:text-slate-400">Beni Hatırla</span>
                        </label>
                        <a href="#" class="text-sm font-semibold text-primary hover:underline">Şifremi Unuttum</a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        :disabled="isLoading"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-4 text-base font-bold text-white transition hover:bg-blue-600 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <template v-if="isLoading">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Giriş Yapılıyor...</span>
                        </template>
                        <template v-else>
                            <span>Giriş Yap</span>
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </template>
                    </button>

                    <!-- Divider -->
                    <div class="relative flex items-center py-4">
                        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                        <span class="mx-4 flex-shrink text-sm text-slate-400">veya</span>
                        <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                    </div>

                    <!-- Demo Button -->
                    <button 
                        type="button"
                        @click="handleDemoLogin"
                        :disabled="isLoading"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-base font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 disabled:opacity-70"
                    >
                        Demo hesabıyla giriş yap
                    </button>
                </form>

            </div>
        </div>

        <!-- Right Side: Branding/Visuals -->
        <div class="relative hidden w-1/2 flex-col items-center justify-center bg-gradient-to-br from-[#3c83f6] to-[#6366f1] p-12 md:flex overflow-hidden">
            
            <!-- Decorative Pattern -->
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            
            <!-- Main Content Area -->
            <div class="relative z-10 flex flex-col items-center text-center">
                
                <!-- Floating Icons Wrapper -->
                <div class="relative mb-12 h-64 w-full max-w-md">
                    
                    <!-- Central Image -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-56 w-56 rounded-3xl bg-white/10 backdrop-blur-xl flex items-center justify-center border border-white/20 shadow-2xl">
                            <div class="text-white/80">
                                <span class="material-symbols-outlined text-8xl">home_iot_device</span>
                            </div>
                        </div>
                    </div>

                    <!-- Floating IoT Elements -->
                    <div class="absolute top-0 right-10 animate-bounce h-12 w-12 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white" style="animation-duration: 3s">
                        <span class="material-symbols-outlined">lightbulb</span>
                    </div>
                    <div class="absolute bottom-4 left-10 animate-bounce h-14 w-14 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white" style="animation-duration: 4s">
                        <span class="material-symbols-outlined">power</span>
                    </div>
                    <div class="absolute top-10 left-16 animate-bounce h-10 w-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-white" style="animation-duration: 3.5s">
                        <span class="material-symbols-outlined">sensors</span>
                    </div>
                </div>

                <h2 class="text-4xl font-extrabold text-white mb-4">Akıllı Ev Yönetiminde Yeni Nesil</h2>
                <p class="text-lg text-blue-100 max-w-sm mb-12">PigaSoft IoT ekosistemi ile tüm cihazları tek bir platformdan yönetin, bayiliğinizi geleceğe taşıyın.</p>
            </div>

            <!-- Stats Bar -->
            <div class="absolute bottom-12 left-0 right-0 z-10 flex justify-center px-8">
                <div class="flex items-center gap-8 rounded-2xl bg-white/10 backdrop-blur-lg border border-white/10 p-6">
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">400+</div>
                        <div class="text-xs font-medium text-blue-100 uppercase tracking-wider">Bayi</div>
                    </div>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">10.000+</div>
                        <div class="text-xs font-medium text-blue-100 uppercase tracking-wider">Cihaz</div>
                    </div>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-white">7/24</div>
                        <div class="text-xs font-medium text-blue-100 uppercase tracking-wider">Destek</div>
                    </div>
                </div>
            </div>

            <!-- Background blobs -->
            <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-blue-400/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const isLoading = ref(false);
const showPassword = ref(false);
const error = ref('');
const errors = reactive({
    email: '',
    password: ''
});

const form = reactive({
    email: '',
    password: '',
    remember: false
});

const validateForm = () => {
    errors.email = '';
    errors.password = '';
    
    if (!form.email) {
        errors.email = 'E-posta adresi gerekli';
        return false;
    }
    if (!form.email.includes('@')) {
        errors.email = 'Geçerli bir e-posta adresi girin';
        return false;
    }
    if (!form.password) {
        errors.password = 'Şifre gerekli';
        return false;
    }
    if (form.password.length < 6) {
        errors.password = 'Şifre en az 6 karakter olmalı';
        return false;
    }
    return true;
};

const handleLogin = async () => {
    if (!validateForm()) return;
    
    isLoading.value = true;
    error.value = '';

    try {
        router.post('/login', {
            email: form.email,
            password: form.password,
            remember: form.remember
        }, {
            onSuccess: () => {
                // Başarılı giriş - Dashboard'a yönlendirilecek
            },
            onError: (err) => {
                if (err.email) {
                    error.value = err.email;
                } else {
                    error.value = 'Giriş yapılırken bir hata oluştu';
                }
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    } catch (e) {
        error.value = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        isLoading.value = false;
    }
};

const handleDemoLogin = () => {
    form.email = 'admin@garagelink.com';
    form.password = 'password';
    handleLogin();
};
</script>