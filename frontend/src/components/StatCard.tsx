
type StatCardProps = {
  title: string
  value: string
  description: string
}

function StatCard({ title, value, description }: StatCardProps) {
  return (
    <div className="stat-card">
      <p>{title}</p>
      <h2>{value}</h2>
      <span>{description}</span>
    </div>
  )
}

export default StatCard
