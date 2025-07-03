import { useForm } from '@inertiajs/react';

export default function FormWrapper({ model = {}, fields, onSubmit }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        ...model,
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setData(name, value);
    };

    const submit = (e) => {
        e.preventDefault();
        onSubmit({ data, post, put });
    };

    return (
        <div className="bg-background p-4 rounded-lg">
            <form onSubmit={submit}>
                {fields.map((field) => (
                    <div key={field.name} className="mb-4">
                        <label htmlFor={field.name} className="block font-semibold mb-1">
                            {field.label}
                        </label>
                        <input
                            id={field.name}
                            name={field.name}
                            type={field.type || 'text'}
                            value={data[field.name] || ''}
                            onChange={handleChange}
                            className="border rounded w-full p-2"
                        />
                        {errors[field.name] && (
                            <p className="text-red-500 text-sm mt-1">{errors[field.name]}</p>
                        )}
                    </div>
                ))}

                <button
                    type="submit"
                    disabled={processing}
                    className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Salvar
                </button>
            </form>
        </div>
    );
}
