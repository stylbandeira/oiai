import { Link, Head } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Welcome(props) {
    return (
        <>
            <h1>
                Welcome
            </h1>
        </>
    );
}
Welcome.layout = page => <MainLayout children={page} />
