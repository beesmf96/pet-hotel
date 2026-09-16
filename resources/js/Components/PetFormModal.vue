<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    pet: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    species: '',
    breed: '',
    age: '',
    special_needs: '',
    photo: null,
});

watch(
    () => props.pet,
    (pet) => {
        form.name = pet?.name ?? '';
        form.species = pet?.species ?? '';
        form.breed = pet?.breed ?? '';
        form.age = pet?.age ?? '';
        form.special_needs = pet?.special_needs ?? '';
        form.photo = null;
    },
    { immediate: true },
);

function submit() {
    if (props.pet) {
        form.patch(`/pets/${props.pet.id}`, {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post('/pets', {
            onSuccess: () => emit('close'),
        });
    }
}

function close() {
    form.reset();
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60" @click.self="close">
            <div class="bg-white border-3 border-ink rounded-2xl shadow-hard-lg w-full max-w-md mx-4 p-6">
                <h2 class="font-display font-bold text-2xl mb-4">
                    {{ pet ? 'Edit Pet' : 'Add Pet' }}
                </h2>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-bold mb-1.5">Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                        />
                        <p v-if="form.errors.name" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-1.5">Species *</label>
                        <input
                            v-model="form.species"
                            type="text"
                            placeholder="e.g. Dog, Cat, Rabbit"
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                        />
                        <p v-if="form.errors.species" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.species }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold mb-1.5">Breed</label>
                            <input
                                v-model="form.breed"
                                type="text"
                                class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                            />
                            <p v-if="form.errors.breed" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.breed }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-1.5">Age (years)</label>
                            <input
                                v-model="form.age"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal"
                            />
                            <p v-if="form.errors.age" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.age }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-1.5">Special Needs</label>
                        <textarea
                            v-model="form.special_needs"
                            rows="2"
                            class="w-full border-2 border-ink rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal resize-none"
                        />
                        <p v-if="form.errors.special_needs" class="mt-1.5 text-sm font-semibold text-coral">
                            {{ form.errors.special_needs }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-1.5">Photo</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm text-moss"
                            @change="form.photo = $event.target.files[0]"
                        />
                        <p v-if="form.errors.photo" class="mt-1.5 text-sm font-semibold text-coral">{{ form.errors.photo }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="text-sm font-bold text-ink bg-white border-3 border-ink px-4 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition"
                            @click="close"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-teal text-cream border-3 border-ink text-sm font-bold px-5 py-2.5 rounded-xl shadow-hard hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-none transition disabled:opacity-50"
                        >
                            {{ pet ? 'Save Changes' : 'Add Pet' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
