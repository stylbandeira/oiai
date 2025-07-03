import { Link, Head } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { FaUser } from 'react-icons/fa';
import CompanyForm from '@/Components/MainComponents/Company/CompanyForm';
import ProductForm from '@/Components/MainComponents/Product/ProductForm';
import DataTable from '@/Components/MainComponents/DataTable';

export default function Welcome(props) {
    const empresas = {
        data: []
    };
    return (
        <>
            <div className="flex justify-content-center">
                <a href="/" rel="noopener noreferrer">
                    <img className="object-contain h-32" src="/storage/img/primary-logo.png" alt="Logo" />
                </a>
            </div>

            <h1>Empresas</h1>
            <div className="container p-2">
                <DataTable
                    baseRoute="/empresas"
                    columns={[
                        { key: 'nome', label: 'Nome' },
                        { key: 'cnpj', label: 'CNPJ' },
                        { key: 'email', label: 'Email' },
                    ]}
                    data={empresas.data}
                />
            </div>
        </>
    );
}
Welcome.layout = page => <MainLayout children={page} />
