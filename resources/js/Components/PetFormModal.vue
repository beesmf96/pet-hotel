<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import FormField from '@/Components/Ui/FormField.vue';
import SectionTitle from '@/Components/Ui/SectionTitle.vue';
import TextInput from '@/Components/Ui/TextInput.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import { PET_TYPES } from '@/petTypes';

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
            <div class="card-hard-lg w-full max-w-md mx-4 p-6">
                <SectionTitle class="mb-4">
                    {{ pet ? 'Edit Pet' : 'Add Pet' }}
                </SectionTitle>

                <form class="space-y-4" @submit.prevent="submit">
                    <FormField label="Name *" :error="form.errors.name">
                        <TextInput v-model="form.name" type="text" />
                    </FormField>

                    <FormField label="Species *" :error="form.errors.species">
                        <TextInput v-model="form.species" as="select">
                            <option value="" disabled>Choose a species…</option>
                            <option v-for="type in PET_TYPES" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </TextInput>
                    </FormField>

                    <div class="grid grid-cols-2 gap-3">
                        <FormField label="Breed" :error="form.errors.breed">
                            <TextInput v-model="form.breed" type="text" />
                        </FormField>

                        <FormField label="Age (years)" :error="form.errors.age">
                            <TextInput v-model="form.age" type="number" min="0" max="100" />
                        </FormField>
                    </div>

                    <FormField label="Special Needs" :error="form.errors.special_needs">
                        <TextInput v-model="form.special_needs" as="textarea" rows="2" />
                    </FormField>

                    <FormField label="Photo" :error="form.errors.photo">
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm text-moss"
                            @change="form.photo = $event.target.files[0]"
                        />
                    </FormField>

                    <div class="flex justify-end gap-3 pt-2">
                        <UiButton variant="white" type="button" @click="close"> Cancel </UiButton>
                        <UiButton type="submit" :disabled="form.processing">
                            {{ pet ? 'Save Changes' : 'Add Pet' }}
                        </UiButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
