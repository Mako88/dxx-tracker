interface FilterButtonProps {
  title: string;
  selected: boolean;
  onClick: () => void;
}

const FilterButton = ({ title, selected, onClick }: FilterButtonProps) => {
  return (
    <a className={`filter ${selected ? "selected" : ""}`} onClick={onClick}>
      {title}&nbsp;
    </a>
  );
};

export default FilterButton;
