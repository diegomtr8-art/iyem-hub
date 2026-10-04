<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Campo from '@/Components/Campo.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    nombre_completo: '',
    email: '',
    telefono: '',
    curp: '',
    rfc: '',
    calle: '',
    codigo_postal: '',
    municipio: '',
    estado: 'Yucatán',
    tipo_persona: 'fisica',
    estado_persona: 'activa',
});

const submit = () => {
    form.post(route('padron.store'));
};

const claseSelect = (invalido) => [
    'block h-10 w-full rounded-md bg-surface text-body text-ink focus:ring-2 focus:ring-focus focus:ring-offset-2',
    invalido ? 'border-danger focus:border-danger' : 'border-line-strong focus:border-focus',
];
</script>

<template>
    <AppLayout title="Nuevo contacto">
        <template #header>
            <span>Nuevo contacto</span>
        </template>

        <div class="mx-auto max-w-2xl">
            <h1 class="text-display-lg text-ink">Nuevo contacto</h1>
            <p class="mt-1 text-body text-ink-600">
                Captura a la persona tal como aparece en su identificación. Solo el nombre es obligatorio.
            </p>

            <form class="mt-6 overflow-hidden rounded-lg border border-line bg-surface shadow-sm" novalidate @submit.prevent="submit">
                <div class="space-y-6 p-5 sm:p-6">
                    <section class="space-y-5" aria-labelledby="grupo-identificacion">
                        <h2 id="grupo-identificacion" class="text-subtitle text-ink">Identificación</h2>

                        <Campo id="nombre_completo" v-slot="campo" etiqueta="Nombre completo" :error="form.errors.nombre_completo">
                            <TextInput
                                :id="campo.id"
                                v-model="form.nombre_completo"
                                class="block w-full"
                                :invalido="campo.invalido"
                                :aria-describedby="campo.describedby"
                                autocomplete="name"
                                required
                                autofocus
                            />
                        </Campo>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Campo id="curp" v-slot="campo" etiqueta="CURP" ayuda="18 caracteres." :error="form.errors.curp">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.curp"
                                    maxlength="18"
                                    autocapitalize="characters"
                                    autocomplete="off"
                                    spellcheck="false"
                                    class="block w-full font-mono uppercase"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                            <Campo id="rfc" v-slot="campo" etiqueta="RFC" ayuda="12 o 13 caracteres." :error="form.errors.rfc">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.rfc"
                                    maxlength="13"
                                    autocapitalize="characters"
                                    autocomplete="off"
                                    spellcheck="false"
                                    class="block w-full font-mono uppercase"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Campo id="tipo_persona" v-slot="campo" etiqueta="Tipo de persona" :error="form.errors.tipo_persona">
                                <select
                                    :id="campo.id"
                                    v-model="form.tipo_persona"
                                    :class="claseSelect(campo.invalido)"
                                    :aria-invalid="campo.invalido ? 'true' : undefined"
                                    :aria-describedby="campo.describedby"
                                >
                                    <option value="fisica">Física</option>
                                    <option value="moral">Moral</option>
                                </select>
                            </Campo>
                            <Campo id="estado_persona" v-slot="campo" etiqueta="Estado en el padrón" :error="form.errors.estado_persona">
                                <select
                                    :id="campo.id"
                                    v-model="form.estado_persona"
                                    :class="claseSelect(campo.invalido)"
                                    :aria-invalid="campo.invalido ? 'true' : undefined"
                                    :aria-describedby="campo.describedby"
                                >
                                    <option value="activa">Activa</option>
                                    <option value="inactiva">Inactiva</option>
                                    <option value="bloqueada">Bloqueada</option>
                                </select>
                            </Campo>
                        </div>
                    </section>

                    <section class="space-y-5 border-t border-line pt-6" aria-labelledby="grupo-contacto">
                        <h2 id="grupo-contacto" class="text-subtitle text-ink">Contacto</h2>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Campo id="email" v-slot="campo" etiqueta="Correo electrónico" :error="form.errors.email">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="email"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    class="block w-full"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                            <Campo id="telefono" v-slot="campo" etiqueta="Teléfono" ayuda="10 dígitos, sin espacios." :error="form.errors.telefono">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.telefono"
                                    type="tel"
                                    inputmode="tel"
                                    autocomplete="tel-national"
                                    maxlength="10"
                                    class="block w-full font-mono"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                        </div>
                    </section>

                    <section class="space-y-5 border-t border-line pt-6" aria-labelledby="grupo-domicilio">
                        <h2 id="grupo-domicilio" class="text-subtitle text-ink">Domicilio</h2>

                        <Campo id="calle" v-slot="campo" etiqueta="Calle y número" :error="form.errors.calle">
                            <TextInput
                                :id="campo.id"
                                v-model="form.calle"
                                autocomplete="street-address"
                                class="block w-full"
                                :invalido="campo.invalido"
                                :aria-describedby="campo.describedby"
                            />
                        </Campo>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Campo id="municipio" v-slot="campo" etiqueta="Municipio" :error="form.errors.municipio">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.municipio"
                                    class="block w-full"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                            <Campo id="codigo_postal" v-slot="campo" etiqueta="Código postal" :error="form.errors.codigo_postal">
                                <TextInput
                                    :id="campo.id"
                                    v-model="form.codigo_postal"
                                    inputmode="numeric"
                                    autocomplete="postal-code"
                                    maxlength="5"
                                    class="block w-full font-mono"
                                    :invalido="campo.invalido"
                                    :aria-describedby="campo.describedby"
                                />
                            </Campo>
                        </div>
                    </section>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <Link
                        :href="route('padron.index')"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-line-strong bg-surface px-4 text-body-strong text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                    >
                        Cancelar
                    </Link>
                    <PrimaryButton :procesando="form.processing">
                        {{ form.processing ? 'Guardando…' : 'Guardar contacto' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
