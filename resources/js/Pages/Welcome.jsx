import MainLayout from '@/Layouts/MainLayout';
import PrimaryButton from '@/Components/Buttons/PrimaryButton';

export default function Welcome(props) {
    const empresas = {
        data: []
    };

    const handleClick = () => {
        console.log('Botão');
    }
    return (
        <>
            <div className="flex justify-content-center">
                <a href="/" rel="noopener noreferrer">
                    <img className="object-contain h-32" src="/storage/img/primary-logo.png" alt="Logo" />
                </a>
            </div>

            <h1>Empresas</h1>
            <div className="container p-2">

                <div className="col">
                    <div className="col-md-6">
                        <div className="row">
                            <PrimaryButton onClick={handleClick} variant="default">
                                Default
                            </PrimaryButton>
                        </div>

                        <div className="row mt-2">
                            <PrimaryButton onClick={handleClick} variant="clean">
                                Default
                            </PrimaryButton>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="row mt-2">
                            <PrimaryButton onClick={handleClick} variant="disabled">
                                Default
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

            </div>
        </>
    );
}
Welcome.layout = page => <MainLayout children={page} />
