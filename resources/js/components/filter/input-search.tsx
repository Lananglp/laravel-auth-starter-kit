import React, { useState, useEffect, useRef } from 'react';
import { Input } from '../ui/input'; // ganti sesuai path Input di project kamu

interface InputSearchProps {
    value?: string;
    onSearch: (value: string) => void;
    placeholder?: string;
    debounceDelay?: number;
    className?: string;
}

const InputSearch: React.FC<InputSearchProps> = ({
    value = '',
    onSearch,
    placeholder = 'Cari...',
    debounceDelay = 500,
    className = '',
}) => {
    const [searchTerm, setSearchTerm] = useState<string>(value);
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

    // Sync prop `value` ke state saat berubah dari luar
    useEffect(() => {
        setSearchTerm(value);
    }, [value]);

    // Debounce input
    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            onSearch(searchTerm);
        }, debounceDelay);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [searchTerm]);

    return (
        <div className="w-full">
            <div className="relative w-full">
                <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg
                        className="w-4 h-4 text-zinc-500 dark:text-zinc-400"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 20 20"
                    >
                        <path
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                        />
                    </svg>
                </div>
                <Input
                    type="text"
                    className={`ps-8 ${className}`}
                    placeholder={placeholder}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                />
            </div>
        </div>
    );
};

export default InputSearch;