import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from "@/components/ui/checkbox";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { CheckCircle2, Info, Building2, FileText, Users, CreditCard, Settings2 } from "lucide-react";
import { Progress } from "@/components/ui/progress";

interface OnboardingStep {
    title: string;
    fields: Record<string, {
        label: string;
        type: string;
        required: boolean;
        options?: string[];
        default?: string;
    }>;
}

interface RequiredIntegration {
    name: string;
    description: string;
    required_for_vat_registered?: boolean;
    required_for_employers?: boolean;
    required_for_limited_companies?: boolean;
}

interface RecommendedGateway {
    name: string;
    description: string;
    use_cases: string[];
}

const stepIcons: Record<string, React.ElementType> = {
    'Company Details': Building2,
    'Contact Details': FileText,
    'Tax Details': FileText,
    'Payroll Details': Users,
    'Bank Details': CreditCard,
    'Preferences': Settings2,
};

export default function UKOnboarding() {
    const { t } = useTranslation();
    const { onboardingSteps, requiredIntegrations, recommendedGateways, flash } = usePage().props as any;

    const [currentStep, setCurrentStep] = useState(0);
    const [formData, setFormData] = useState<Record<string, any>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const steps = Object.entries(onboardingSteps || {}).map(([key, step]: [string, OnboardingStep]) => ({
        key,
        ...step,
    }));

    const totalSteps = steps.length;
    const progress = ((currentStep + 1) / totalSteps) * 100;

    useEffect(() => {
        if (flash?.success) {
            router.visit('/dashboard');
        }
    }, [flash]);

    const handleChange = (field: string, value: any) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleNext = () => {
        if (currentStep < totalSteps - 1) {
            setCurrentStep(currentStep + 1);
        }
    };

    const handlePrevious = () => {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    };

    const handleSubmit = () => {
        setIsSubmitting(true);
        router.post('/uk/onboarding', formData, {
            onSuccess: () => setIsSubmitting(false),
            onError: () => setIsSubmitting(false),
        });
    };

    const currentStepData = steps[currentStep];
    const StepIcon = stepIcons[currentStepData?.title] || FileText;

    return (
        <AuthenticatedLayout>
            <Head title="UK Onboarding" />
            
            <div className="container mx-auto py-6 max-w-3xl">
                <div className="flex items-center gap-2 mb-6">
                    <Building2 className="h-6 w-6" />
                    <h1 className="text-2xl font-bold">UK Onboarding</h1>
                </div>

                {flash?.success && (
                    <Alert className="mb-6 border-green-200 bg-green-50">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertTitle className="text-green-800">Success</AlertTitle>
                        <AlertDescription className="text-green-700">{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert className="mb-6 border-red-200 bg-red-50">
                        <AlertTitle className="text-red-800">Error</AlertTitle>
                        <AlertDescription className="text-red-700">{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="mb-6">
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm text-gray-600">Step {currentStep + 1} of {totalSteps}</span>
                        <span className="text-sm text-gray-600">{currentStepData?.title}</span>
                    </div>
                    <Progress value={progress} className="h-2" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <StepIcon className="h-5 w-5" />
                            {currentStepData?.title}
                        </CardTitle>
                        <CardDescription>
                            Please provide your {currentStepData?.title.toLowerCase()} information
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {currentStepData?.fields && Object.entries(currentStepData.fields).map(([fieldName, fieldConfig]) => (
                            <div key={fieldName} className="space-y-2">
                                <Label htmlFor={fieldName}>
                                    {fieldConfig.label}
                                    {fieldConfig.required && <span className="text-red-500 ml-1">*</span>}
                                </Label>
                                
                                {fieldConfig.type === 'text' || fieldConfig.type === 'email' || fieldConfig.type === 'tel' || fieldConfig.type === 'url' ? (
                                    <Input
                                        id={fieldName}
                                        type={fieldConfig.type}
                                        value={formData[fieldName] || ''}
                                        onChange={(e) => handleChange(fieldName, e.target.value)}
                                        required={fieldConfig.required}
                                    />
                                ) : fieldConfig.type === 'select' ? (
                                    <Select
                                        value={formData[fieldName] || fieldConfig.default || ''}
                                        onValueChange={(value) => handleChange(fieldName, value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {fieldConfig.options?.map((option: string) => (
                                                <SelectItem key={option} value={option}>
                                                    {option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : fieldConfig.type === 'checkbox' ? (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id={fieldName}
                                            checked={formData[fieldName] || false}
                                            onCheckedChange={(checked) => handleChange(fieldName, checked)}
                                        />
                                        <Label htmlFor={fieldName} className="cursor-pointer">
                                            Yes
                                        </Label>
                                    </div>
                                ) : null}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handlePrevious}
                        disabled={currentStep === 0}
                    >
                        Previous
                    </Button>

                    {currentStep < totalSteps - 1 ? (
                        <Button onClick={handleNext}>
                            Next
                        </Button>
                    ) : (
                        <Button onClick={handleSubmit} disabled={isSubmitting}>
                            {isSubmitting ? 'Completing...' : 'Complete Setup'}
                        </Button>
                    )}
                </div>

                <div className="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Required Integrations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {requiredIntegrations?.map((integration: RequiredIntegration) => (
                                    <div key={integration.name} className="flex items-start gap-2">
                                        <Info className="h-4 w-4 mt-0.5 text-blue-500" />
                                        <div>
                                            <p className="font-medium text-sm">{integration.name}</p>
                                            <p className="text-xs text-gray-600">{integration.description}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
