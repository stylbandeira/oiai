import { Link, Head } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { FaUser } from 'react-icons/fa';
import CompanyForm from '@/Components/MainComponents/Company/CompanyForm';

export default function Welcome(props) {
    return (
        <>
            <div className="flex justify-content-center">
                <a href="/" rel="noopener noreferrer">
                    <img className="object-contain h-32" src="/storage/img/primary-logo.png" alt="Logo" />
                </a>
            </div>
            <div className="mail">
                <div className="mail-card">
                    <h1>Olá, Fulano</h1>
                    <p className='font-ubuntu font-italic'>Clique no botão abaixo para redefinir sua senha:</p>

                    <a href='/'
                        className="btn btn-success">
                        Redefinir Senha
                    </a>

                    <hr />

                    <div className="align-self-middle">
                        <p>Se você não solicitou isso, ignore este e-mail</p>
                    </div>
                </div>
            </div>

            <CompanyForm></CompanyForm>
        </>
    );
}
Welcome.layout = page => <MainLayout children={page} />
