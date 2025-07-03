import FormWrapper from '@/Components/MainComponents/FormWrapper';

export default function CompanyForm() {
    const fields = [
        { name: 'name', label: 'Nome da Empresa' },
        { name: 'cnpj', label: 'CNPJ' },
        { name: 'img', label: 'Imagem', type: 'file' },
    ];

    const handleSubmit = ({ data, post }) => {
        console.log(data);
        // post(route('companies.store'));
    };

    return (
        <div>
            <h1 className="text-2xl font-bold mb-4 text-center">Criar Empresa</h1>
            <FormWrapper fields={fields} onSubmit={handleSubmit} />
        </div>
    );
}
